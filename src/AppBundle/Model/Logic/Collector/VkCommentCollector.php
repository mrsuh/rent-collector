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

class VkCommentCollector implements CollectorInterface
{
    private $request;
    private $logger;
    private $storage;
    private $parser_id;
    private $parser_link;
    private $parser_datetime;

    /**
     * VkCommentCollector constructor.
     * @param VkPublicRequest   $request
     * @param IdParserFactory   $parser_id_factory
     * @param LinkParserFactory $parser_link_factory
     * @param Logger            $logger
     * @param string            $file_dir
     */
    public function __construct(
        VkPublicRequest $request,
        IdParserFactory $parser_id_factory,
        LinkParserFactory $parser_link_factory,
        DateTimeParserFactory $parser_datetime_factory,
        Logger $logger,
        string $file_dir)
    {
        $this->request = $request;
        $this->logger  = $logger;
        $this->storage = new FileStorage($file_dir);

        $source_type           = Source::TYPE_VK_COMMENT;
        $this->parser_id       = $parser_id_factory->init($source_type);
        $this->parser_link     = $parser_link_factory->init($source_type);
        $this->parser_datetime = $parser_datetime_factory->init($source_type);
    }

    /**
     * @param string $file_name
     * @return int
     */
    private function getIdFromFile(string $file_name)
    {
        return $this->storage->exists($file_name) ? $this->storage->get($file_name) : 1;
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

        $notes = [];
        foreach ($items as $item) {

            $id        = $source->getId() . '-' . $this->parser_id->parse($item);
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
}

