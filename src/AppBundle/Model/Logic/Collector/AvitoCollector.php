<?php

namespace AppBundle\Model\Logic\Collector;

use AppBundle\Exception\ParseException;
use AppBundle\Model\Logic\Parser\DateTime\DateTimeParserFactory;
use AppBundle\Request\AvitoRequest;
use AppBundle\Storage\FileStorage;
use Monolog\Logger;
use Schema\Parse\Record\Source;
use PHPHtmlParser\Dom;

class AvitoCollector implements CollectorInterface
{
    private $request;
    private $logger;
    private $storage;
    private $parser_datetime;
    private $last_hours;
    private $unique_ids;

    /**
     * AvitoCollector constructor.
     * @param AvitoRequest          $request
     * @param DateTimeParserFactory $parser_datetime_factory
     * @param Logger                $logger
     * @param string                $file_dir
     * @param int                   $last_hours
     */
    public function __construct(
        AvitoRequest $request,
        DateTimeParserFactory $parser_datetime_factory,
        Logger $logger,
        string $file_dir,
        int $last_hours
    )
    {
        $this->request         = $request;
        $this->logger          = $logger;
        $this->storage         = new FileStorage($file_dir);
        $this->parser_datetime = $parser_datetime_factory->init(Source::TYPE_AVITO);

        $this->last_hours = $last_hours;
        $this->unique_ids = [];
    }

    /**
     * @param Source $source
     * @return string
     */
    private function getConfigName(Source $source)
    {
        return 'config_' . $source->getId();
    }

    /**
     * @param Source $source
     * @return AvitoConfig
     */
    private function getConfigFromFile(Source $source)
    {
        $config_name = $this->getConfigName($source);

        $date       = (new \DateTime())->modify(sprintf('- %s hours', $this->last_hours));
        $new_config =
            (new AvitoConfig())
                ->setPage(1)
                ->setTimestamp($date->getTimestamp())
                ->setFinish(false);

        if (!$this->storage->exists($config_name)) {

            return $new_config;
        }

        $instance = $this->storage->get($config_name);

        $config = unserialize($instance);

        if (!($config instanceof AvitoConfig)) {

            return $new_config;
        }

        return $config;
    }

    /**
     * @param Source       $source
     * @param VkWallConfig $config
     * @return bool
     */
    private function setConfigToFile(Source $source, AvitoConfig $config)
    {
        $config_name = $this->getConfigName($source);

        return $this->storage->put($config_name, serialize($config));
    }

    /**
     * @param Source $source
     * @return array
     * @throws ParseException
     */
    public function collect(Source $source)
    {
        $this->logger->debug('Processing collect...', [
            'source_id'   => $source->getId(),
            'source_type' => $source->getType()
        ]);

        try {

            $config = $this->getConfigFromFile($source);

            if ($config->isFinish()) {
                $this->setConfigToFile($source, $config->setPage(1)->setFinish(false));

                $this->logger->debug('There are no more notes', [
                    'source_id'   => $source->getId(),
                    'source_type' => $source->getType()
                ]);

                return [];
            }

            usleep(200000);

            $link_list = $source->getLink();

            $this->logger->debug('Collect list requesting...', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'link'        => $link_list,
                'page'        => $config->getPage()
            ]);

            $response = $this->request->getList($link_list, $config->getPage());

            $this->logger->debug('Collect list requesting... done', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'link'        => $link_list,
                'page'        => $config->getPage()
            ]);

            $contents = $response->getBody()->getContents();

            $notes = $this->getLinks($source, $contents);

            $raws   = [];
            $finish = false;
            foreach ($notes as $raw) {

                if ($raw->getTimestamp() < $config->getTimestamp()) {

                    $finish = true;

                    $this->logger->debug('Done by timestamp', [
                        'source_id'   => $source->getId(),
                        'source_type' => $source->getType(),
                    ]);

                    break;
                }

                if (in_array($raw->getId(), $this->unique_ids)) {

                    continue;
                }

                $this->unique_ids[] = $raw->getId();

                try {

                    usleep(200000);

                    $this->logger->debug('Request item', [
                        'link' => $raw->getLink()
                    ]);

                    $raw_response = $this->request->getRecord($raw->getLink());

                } catch (\Exception $e) {
                    $this->logger->error('Request error', [
                        'source_id'   => $source->getId(),
                        'source_type' => $source->getType(),
                        'exception'   => $e->getMessage()
                    ]);

                    continue;
                }

                $raw_content = $raw_response->getBody()->getContents();

                $raw_dom = new Dom();
                $raw_dom->load($raw_content);

                $raw->setContent($raw_dom);
                $raw->setLink('https://www.avito.ru/' . $raw->getLink());

                $raws[] = $raw;
            }

            if (empty($raws)) {
                $this->setConfigToFile($source,
                    $config
                        ->setPage(1)
                        ->setFinish(false)
                );

                return $raws;
            }

            $this->setConfigToFile($source,
                $config
                    ->setFinish($finish)
                    ->setTimestamp($finish ? date('U') : $config->getTimestamp())
                    ->setPage($finish ? 1 : $config->getPage() + 1)
            );

        } catch (\Exception $e) {
            $this->logger->error('Collector error', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'exception'   => $e->getMessage()
            ]);

            return [];
        }

        $this->logger->debug('Processing collect... done', [
            'source_id'   => $source->getId(),
            'source_type' => $source->getType(),
        ]);

        return $raws;
    }

    /**
     * @param string $content
     * @return RawData[]
     */
    private function getLinks(Source $source, string $content)
    {
        $dom = new Dom();

        $dom->load($content);

        $list = $dom->find('.catalog-list .description-title');

        $notes = [];
        foreach ($list as $elem) {

            $link_elems = $elem->find('.description-title-link');

            if (!array_key_exists(0, $link_elems)) {

                continue;
            }

            $link_elem = $link_elems[0];

            $link      = preg_replace('/^\//', '', $link_elem->href);

            preg_match('/\._(\d+)$/', $link, $match);

            if (!array_key_exists(1, $match)) {

                continue;
            }

            $id = $source->getId() . '-' . $match[1];

            $timestamp = $this->parser_datetime->parse($elem);

            $notes[] =
                (new RawData())
                    ->setId($id)
                    ->setLink($link)
                    ->setTimestamp($timestamp);
        }

        return $notes;
    }
}

