<?php

namespace AppBundle\Model\Logic\Collector;

use AppBundle\Exception\ParseException;
use AppBundle\Model\Logic\Parser\DateTime\DateTimeParserFactory;
use AppBundle\Model\Logic\Parser\Id\IdParserFactory;
use AppBundle\Model\Logic\Parser\Link\LinkParserFactory;
use AppBundle\Request\VkPublicRequest;
use AppBundle\Storage\FileStorage;
use Monolog\Logger;
use Schema\Parse\Record\Source;

class VkWallCollector implements CollectorInterface
{
    private $request;
    private $logger;
    private $storage;
    private $parser_id;
    private $parser_link;
    private $parser_datetime;
    private $last_hours;

    private $unique_ids;

    /**
     * VkWallCollector constructor.
     * @param VkPublicRequest $request
     * @param string          $file_dir
     */
    public function __construct(
        VkPublicRequest $request,
        IdParserFactory $parser_id_factory,
        LinkParserFactory $parser_link_factory,
        DateTimeParserFactory $parser_datetime_factory,
        Logger $logger,
        string $file_dir,
        int $last_hours
    )
    {
        $this->request    = $request;
        $this->logger     = $logger;
        $this->storage    = new FileStorage($file_dir);
        $this->last_hours = $last_hours;
        $this->unique_ids = [];

        $source_type           = Source::TYPE_VK_WALL;
        $this->parser_id       = $parser_id_factory->init($source_type);
        $this->parser_link     = $parser_link_factory->init($source_type);
        $this->parser_datetime = $parser_datetime_factory->init($source_type);
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
     * @return VkWallConfig
     */
    private function getConfigFromFile(Source $source)
    {
        $config_name = $this->getConfigName($source);
        $new_config  = (new VkWallConfig())->setOffset(0)->setFinish(false);

        if (!$this->storage->exists($config_name)) {

            return $new_config;
        }

        $instance = $this->storage->get($config_name);

        $config = unserialize($instance);

        if (!($config instanceof VkWallConfig)) {

            return $new_config;
        }

        return $config;
    }

    /**
     * @param Source       $source
     * @param VkWallConfig $config
     * @return bool
     */
    private function setConfigToFile(Source $source, VkWallConfig $config)
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
            'source_type' => $source->getType(),
        ]);

        try {

            $params = json_decode($source->getParameters(), true);

            if (!is_array($params)) {

                $this->logger->error('Source params has invalid json', [
                    'source_id'   => $source->getId(),
                    'source_type' => $source->getType(),
                    'parameters'  => $source->getParameters()
                ]);

                return [];
            }

            foreach (['owner_id', 'count'] as $key) {
                if (!array_key_exists($key, $params)) {
                    $this->logger->error('Source params has not key', [
                        'key'         => $key,
                        'source_id'   => $source->getId(),
                        'source_type' => $source->getType(),
                        'parameters'  => $source->getParameters()
                    ]);

                    return [];
                }
            }

            $config = $this->getConfigFromFile($source);

            if ($config->isFinish()) {
                $this->setConfigToFile($source, $config->setOffset(0)->setFinish(false));

                $this->logger->debug('There is no more notes', [
                    'source_id'   => $source->getId(),
                    'source_type' => $source->getType()
                ]);

                return [];
            }

            usleep(200000);

            $this->logger->debug('Collect requesting...', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'params'      => $params
            ]);

            $params['offset'] = $config->getOffset();

            $items_raw = $this->request($source, $params);

            $items     = [];
            $finish    = false;
            $timestamp = (new \DateTime())->modify('- ' . $this->last_hours . ' hours')->getTimestamp();

            foreach ($items_raw as $item) {

                if (!array_key_exists('id', $item)) {
                    $this->logger->error('Item has not key "id"', [
                        'source_id'   => $source->getId(),
                        'source_type' => $source->getType(),
                        'params'      => $params
                    ]);

                    break;
                }

                $unique_id = $source->getId() . '-' . $item['id'];

                if (in_array($unique_id, $this->unique_ids)) {

                    continue;
                }

                $this->unique_ids[] = $unique_id;

                if (
                    array_key_exists('marked_as_ads', $item) &&
                    $item['marked_as_ads']
                ) {

                    continue;
                }

                if (
                    $timestamp > $item['date'] &&
                    array_key_exists('is_pinned', $item) &&
                    $item['is_pinned']
                ) {

                    continue;
                }

                if ($timestamp > $item['date']) {
                    $finish = true;

                    break;
                }

                $items[] = $item;
            }

            $config
                ->setFinish($finish)
                ->setOffset($finish ? 0 : $config->getOffset() + 10);

            $this->setConfigToFile($source, $config);

            $this->logger->debug('Processing collect... done', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Processing collect error', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'error'       => $e->getMessage()
            ]);

            return [];
        }

        $notes = [];
        foreach ($items as $item) {

            $id        = $this->parser_id->parse($item);
            $link      = $this->parser_link->parse($source, $id);
            $timestamp = $this->parser_datetime->parse($item);

            $notes[] =
                (new RawData())
                    ->setId($id)
                    ->setLink($link)
                    ->setTimestamp($timestamp)
                    ->setContent($item);
        }

        return $notes;
    }

    /**
     * @param Source $source
     * @param array  $params
     * @return array
     */
    private function request(Source $source, array $params)
    {
        $response_raw = $this->request->getWallRecords($params);

        $contents = $response_raw->getBody()->getContents();

        $this->logger->debug('Collect requesting... done', [
            'source_id'   => $source->getId(),
            'source_type' => $source->getType(),
            'params'      => $params
        ]);

        $data = json_decode($contents, true);

        if (!is_array($data)) {

            $this->logger->error('Response has invalid json', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'response'    => $contents
            ]);

            return [];
        }

        if (!array_key_exists('response', $data)) {

            $this->logger->error('Response has not key "response"', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'response'    => $data
            ]);

            return [];
        }

        if (!array_key_exists('items', $data['response'])) {

            $this->logger->error('Response has not key "items"', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'response'    => $data
            ]);

            return [];
        }

        return $data['response']['items'];
    }
}

