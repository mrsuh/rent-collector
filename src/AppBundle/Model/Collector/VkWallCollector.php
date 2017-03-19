<?php

namespace AppBundle\Model\Collector;

use AppBundle\Exception\ParseException;
use AppBundle\Service\Client\Http;
use GuzzleHttp\Psr7\Request;

class VkWallCollector
{
    private $http_client;
    private $file_id;

    public function __construct(Http $http_client, $file_dir)
    {
        $this->http_client = $http_client;
        $this->file_id     = $file_dir . '/vk-com-wall';
    }

    private function getId($file_prefix)
    {
        return file_exists($this->file_id . $file_prefix) ? file_get_contents($this->file_id . $file_prefix) : 1;
    }

    private function setId($file_prefix, $id)
    {
        return file_put_contents($this->file_id . $file_prefix, $id);
    }

    public function collect(array $config, $debug = false)
    {
        $params                     = $config['data'];
        $file_prefix                = $params['owner_id'];
        $params['start_comment_id'] = $this->getId($file_prefix);

        if ($debug) {
            echo 'last id: ' . $this->getId($file_prefix) . PHP_EOL;
        }

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

        $this->setId($file_prefix, $end_item['id']);

        return $items;
    }
}

