<?php

namespace AppBundle\Model\Collector;

use AppBundle\Exception\ParseException;
use AppBundle\Service\Client\Http;
use AppBundle\Storage\FileStorage;
use GuzzleHttp\Psr7\Request;

class VkCommentCollector
{
    private $http_client;
    private $storage;

    public function __construct(Http $http_client, $file_dir)
    {
        $this->http_client = $http_client;
        $this->storage = new FileStorage($file_dir);
    }

    private function getId($file_name)
    {
        return $this->storage->exists($file_name) ? $this->storage->get($file_name) : 1;
    }

    private function setId($file_name, $id)
    {
        return $this->storage->put($file_name, $id);
    }

    public function collect(array $config, $debug = false)
    {
        $params                     = $config['data'];

        $file_name = 'vk-com-comment' . $params['group_id'] . '-' . $params['topic_id'];

        $params['start_comment_id'] = $this->getId($file_name);

        usleep(200);
        $response = $this->http_client->send(new Request('GET', $config['url']), ['query' => $params]);

        $contents = $response->getBody()->getContents();

        $data = json_decode($contents, true);

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

