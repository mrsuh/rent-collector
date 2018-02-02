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
    private $period;
    private $unique_ids;

    /**
     * AvitoCollector constructor.
     * @param AvitoRequest          $request
     * @param DateTimeParserFactory $parser_datetime_factory
     * @param Logger                $logger
     * @param string                $file_dir
     * @param string                $period
     */
    public function __construct(
        AvitoRequest $request,
        DateTimeParserFactory $parser_datetime_factory,
        Logger $logger,
        string $file_dir,
        string $period
    )
    {
        $this->request         = $request;
        $this->logger          = $logger;
        $this->storage         = new FileStorage($file_dir);
        $this->parser_datetime = $parser_datetime_factory->init(Source::TYPE_AVITO);

        $this->period     = $period;
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

        $date       = (new \DateTime())->modify(sprintf('- %s', $this->period));
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

            usleep(1000000);

            $link_list = $source->getLink();

            $this->logger->debug('Collect list requesting...', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'link'        => $link_list,
                'page'        => $config->getPage()
            ]);

            try {

                $response = $this->request->getList($link_list, $config->getPage());

            } catch (\Exception $e) {

                $this->logger->error('Collect list requesting...', [
                    'source_id'   => $source->getId(),
                    'source_type' => $source->getType(),
                    'link'        => $link_list,
                    'page'        => $config->getPage(),
                    'exception'   => $e->getMessage()
                ]);

                $this->setConfigToFile($source,
                    $config
                        ->setPage(1)
                        ->setFinish(false)
                );

                return [];
            }

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

                    $this->logger->debug('Exclude by unique id', [
                        'source_id'   => $source->getId(),
                        'source_type' => $source->getType(),
                        'unique_id'   => $raw->getId()
                    ]);

                    continue;
                }

                $this->unique_ids[] = $raw->getId();

                try {

                    usleep(300000);

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

                $raw->setContent($raw_content);

                $raw->setLink('https://www.avito.ru/' . $raw->getLink());

                $raws[] = $raw;
            }

            if (empty($raws)) {

                $this->logger->debug('Processing collect... done', [
                    'source_id'   => $source->getId(),
                    'source_type' => $source->getType(),
                    'notes'       => count($raws)
                ]);

                $this->setConfigToFile($source,
                    $config
                        ->setTimestamp(date('U'))
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

        $list = $dom->find('.js-catalog-item-enum');

        $notes = [];
        foreach ($list as $elem) {

            $link_elems = $elem->find('.item-link');

            if (count($link_elems) === 0) {

                continue;
            }

            $link_elem = $link_elems[0];

            $link = preg_replace('/^\//', '', $link_elem->href);

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

        unset($dom);

        return $notes;
    }
}

