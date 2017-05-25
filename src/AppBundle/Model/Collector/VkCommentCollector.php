<?php

namespace AppBundle\Model\Collector;

use AppBundle\Exception\ParseException;
use AppBundle\Request\VkPublicRequest;
use AppBundle\Service\Client\Client;
use AppBundle\Storage\FileStorage;
use GuzzleHttp\Psr7\Request;

class VkCommentCollector implements CollectorInterface
{
    private $request;
    private $storage;

    /**
     * VkCommentCollector constructor.
     * @param VkPublicRequest $request
     * @param string          $file_dir
     */
    public function __construct(VkPublicRequest $request, string $file_dir)
    {
        $this->request = $request;
        $this->storage = new FileStorage($file_dir);
    }

    /**
     * @param string $file_name
     * @return int
     */
    private function getId(string $file_name): int
    {
        return $this->storage->exists($file_name) ? $this->storage->get($file_name) : 1;
    }

    /**
     * @param string $file_name
     * @param int    $id
     * @return bool
     */
    private function setId(string $file_name, int $id): bool
    {
        return $this->storage->put($file_name, $id);
    }

    /**
     * @param array $config
     * @param bool  $debug
     * @return array
     * @throws ParseException
     */
    public function collect(array $config, bool $debug = false): array
    {
        $params                     = $config['data'];

        $file_name = 'vk-com-comment-' . $params['group_id'] . '-' . $params['topic_id'];

        $params['start_comment_id'] = $this->getId($file_name);

        usleep(200);
        $response = $this->request->getCommentRecords($params);

        $data = json_decode($response->getBody()->getContents(), true);

        if (!array_key_exists('response', $data)) {
            throw new ParseException('Has not key "response" in response');
        }

        if (!array_key_exists('items', $data['response'])) {
            throw new ParseException('Has not key "items" in response');
        }

        $items = $data['response']['items'];

        $end_item = end($items);

        if ($debug) {
            echo 'last id end: ' . $end_item['id'] . PHP_EOL;
        }

        if ((int)$this->getId($file_name) === (int)$end_item['id']) {
            return [];
        }

        $this->setId($file_name, $end_item['id']);

        return $items;
    }
}

