<?php

namespace AppBundle\Model\Collector;

use AppBundle\Exception\ParseException;
use AppBundle\Service\Client\Http;
use GuzzleHttp\Psr7\Request;

class VkCommentCollector
{
    private $http_client;
    private $file_id;

    public function __construct(Http $http_client, $dir_id)
    {
        $this->http_client = $http_client;
        $this->file_id     = $dir_id . '/vk-com-comment';
    }

    private function getId($file_postfix)
    {
        return file_exists($this->file_id . '-' . $file_postfix) ? file_get_contents($this->file_id . '-' . $file_postfix) : 1;
    }

    private function setId($file_postfix, $id)
    {
        return file_put_contents($this->file_id . '-' . $file_postfix, $id);
    }

    public function collect(array $config, $debug = false)
    {
        $params                     = $config['data'];
        $file_postfix               = $params['group_id'] . '-' . $params['topic_id'];
        $params['start_comment_id'] = $this->getId($file_postfix);

        if ($debug) {
            echo 'last id: ' . $this->getId($file_postfix) . PHP_EOL;
        }

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

        if ((int)$this->getId($file_postfix) === (int)$end_item['id']) {
            return [];
        }

        $this->setId($file_postfix, $end_item['id']);

        return $items;
    }
}

