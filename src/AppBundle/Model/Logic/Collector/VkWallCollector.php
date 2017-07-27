<?php

namespace AppBundle\Model\Logic\Collector;

use AppBundle\Exception\ParseException;
use AppBundle\Request\VkPublicRequest;
use AppBundle\Storage\FileStorage;
use Monolog\Logger;
use Schema\ParseList\Source;

class VkWallCollector implements CollectorInterface
{
    private $request;
    private $logger;
    private $storage;

    /**
     * VkWallCollector constructor.
     * @param VkPublicRequest $request
     * @param string          $file_dir
     */
    public function __construct(VkPublicRequest $request, Logger $logger, string $file_dir)
    {
        $this->request = $request;
        $this->logger  = $logger;
        $this->storage = new FileStorage($file_dir);
    }

    /**
     * @param string $file_name
     * @return VkWallConfig
     */
    private function getConfigFromFile(string $file_name)
    {
        $new_config = (new VkWallConfig())->setOffset(0)->setFinish(false);

        if ($this->storage->exists($file_name)) {

            return $new_config;
        }

        $instance = $this->storage->get($file_name);

        $config = unserialize($instance);

        if (!($config instanceof VkWallConfig)) {

            return $new_config;
        }

        return $config;
    }

    /**
     * @param string       $file_name
     * @param VkWallConfig $config
     * @return bool
     */
    private function setConfigToFile(string $file_name, VkWallConfig $config)
    {
        return $this->storage->put($file_name, serialize($config));
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

            $file_name = 'source_' . $source->getId();

            $config = $this->getConfigFromFile($file_name);

            if ($config->isFinish()) {
                $this->setConfigToFile($file_name, $config->setOffset(0)->setFinish(false));

                $this->logger->debug('There is no more notes', [
                    'source_id'   => $source->getId(),
                    'source_type' => $source->getType()
                ]);

                return [];
            }

            $this->logger->debug('', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'offset'      => $config->getOffset()
            ]);

            $params['offset'] = $config->getOffset();
            $timestamp        = (new \DateTime())->modify('- 1 hour')->getTimestamp();

            $this->logger->debug('Collect sleeping...', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType()
            ]);

            usleep(200);

            $this->logger->debug('Collect sleeping... done', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType()
            ]);

            $this->logger->debug('Collect requesting...', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'params'      => $params
            ]);

            $response_raw = $this->request->getWallRecords($params);

            $this->logger->debug('Collect requesting... done', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'params'      => $params
            ]);

            $data = json_decode($response_raw, true);

            if (!is_array($data)) {

                $this->logger->error('Response has invalid json', [
                    'source_id'   => $source->getId(),
                    'source_type' => $source->getType(),
                    'response'    => $response_raw
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

            $items_raw = $data['response']['items'];
            $items     = [];
            $finish    = false;
            foreach ($items_raw as $item) {

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

            if ($finish) {
                $this->setConfigToFile($file_name, $config->setOffset(0)->setFinish(true));
            } else {
                $this->setConfigToFile($file_name, $config->setFinish($config->getOffset() + 5)->setFinish(false));
            }

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

        return $items;
    }
}

