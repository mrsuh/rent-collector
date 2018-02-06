<?php

namespace AppBundle\Model\Logic\Collector;

use AppBundle\Exception\ParseException;
use AppBundle\Model\Logic\Parser\ParserFactory;
use AppBundle\Request\VkPublicRequest;
use AppBundle\Storage\FileStorage;
use Monolog\Logger;
use Schema\Parse\Record\Source;

class VkCommentCollector implements CollectorInterface
{
    private $request;
    private $logger;
    private $storage;
    private $parser_factory;

    private $period;

    /**
     * VkCommentCollector constructor.
     * @param VkPublicRequest $request
     * @param ParserFactory   $parser_factory
     * @param Logger          $logger
     * @param string          $file_dir
     * @param string          $period
     */
    public function __construct(
        VkPublicRequest $request,
        ParserFactory $parser_factory,
        Logger $logger,
        string $file_dir,
        string $period)
    {
        $this->request = $request;
        $this->logger  = $logger;
        $this->storage = new FileStorage($file_dir);

        $this->parser_factory = $parser_factory;

        $this->period = $period;
    }

    /**
     * @param string $file_name
     * @return int
     */
    private function getIdFromFile(string $file_name)
    {
        return $this->storage->exists($file_name) ? $this->storage->get($file_name) : 9999999;
    }

    /**
     * @param string $file_name
     * @param int    $id
     * @return bool
     */
    private function setIdToFile(string $file_name, int $id)
    {
        return $this->storage->put($file_name, $id);
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

            foreach (['group_id', 'topic_id', 'count'] as $key) {
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

            $file_name = 'source_' . $source->getId();

            $params['start_comment_id'] = $this->getIdFromFile($file_name);

            $this->logger->debug('Collect sleeping...', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType()
            ]);

            usleep(200000);

            $this->logger->debug('Collect sleeping... done', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType()
            ]);

            $this->logger->debug('Collect requesting...', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'params'      => $params
            ]);

            $response = $this->request->getCommentRecords($params);

            $this->logger->debug('Collect requesting... done', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'params'      => $params
            ]);

            $response_raw = $response->getBody()->getContents();

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

            $items = $data['response']['items'];

            $end_item = end($items);

            if (!array_key_exists('id', $end_item)) {
                $this->logger->error('Item has not key "id"', [
                    'source_id'   => $source->getId(),
                    'source_type' => $source->getType(),
                    'item'        => $end_item
                ]);

                return [];
            }

            $this->logger->debug('Last id', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'last_id'     => $end_item['id']
            ]);

            if ((int)$this->getIdFromFile($file_name) === (int)$end_item['id']) {

                $this->logger->debug('There are not more notes', [
                    'source_id'   => $source->getId(),
                    'source_type' => $source->getType(),
                    'last_id'     => $end_item['id']
                ]);

                return [];
            }

            $this->setIdToFile($file_name, $end_item['id']);

        } catch (\Exception $e) {
            $this->logger->error('Collector error', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'error'       => $e->getMessage()
            ]);

            return [];
        }

        $this->logger->debug('Processing collect... done', [
            'source_id'   => $source->getId(),
            'source_type' => $source->getType(),
        ]);

        $notes          = [];
        $timestamp_last = (new \DateTime())->modify(sprintf('- %s', $this->period))->getTimestamp();
        foreach ($items as $item) {
            $parser = $this->parser_factory->init($source, $item);
            $id     = $parser->id();

            $this->logger->debug("Handle item", [
                'id'   => $id,
                'item' => $item
            ]);

            $timestamp = $parser->timestamp();

            if ($timestamp_last > $timestamp) {
                $this->logger->debug('Handle item $timestamp_last > $timestamp', [
                    'id'             => $id,
                    'item_timestamp' => $timestamp,
                    'last_timestamp' => $timestamp_last
                ]);
                continue;
            }

            $notes[] =
                (new RawData())
                    ->setId($source->getId() . '-' . $id)
                    ->setLink($parser->link($id))
                    ->setTimestamp($timestamp)
                    ->setContent($item);
        }

        return $notes;
    }
}

