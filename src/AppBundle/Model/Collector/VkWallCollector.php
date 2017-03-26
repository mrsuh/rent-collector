<?php

namespace AppBundle\Model\Collector;

use AppBundle\Exception\ParseException;
use AppBundle\Service\Client\Http;
use AppBundle\Storage\FileStorage;
use GuzzleHttp\Psr7\Request;

class VkWallCollector
{
    private $http_client;
    private $storage;

    public function __construct(Http $http_client, $file_dir)
    {
        $this->http_client = $http_client;
        $this->storage = new FileStorage($file_dir);
    }

    private function getData($file_name)
    {
        return $this->storage->exists($file_name) ? $this->storage->get($file_name) : ['offset' => 0, 'finish' => false];
    }

    private function setData($file_name, $data)
    {
        return $this->storage->put($file_name, $data);
    }

    public function collect(array $config, $debug = true)
    {
        $params = $config['data'];
        $id     = 'vk-com-wall' . $params['owner_id'];

        $data = $this->getData($id);

        if ($data['finish']) {
            $this->setData($id, ['offset' => 0, 'finish' => false]);

            return [];
        }

        $offset = $data['offset'];

        if ($debug) {
            echo 'OFFSET: ' . $offset . PHP_EOL;
        }

        $params['offset'] = $data['offset'];
        $timestamp        = (new \DateTime())->modify('- ' . $config['date'])->getTimestamp();

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

        $finish = false;
        foreach ($items as $item) {
            if ($timestamp > $item['date']) {
                $finish = true;
                break;
            }
        }

        if ($finish) {
            $this->setData($id, ['offset' => 0, 'finish' => true]);
        } else {
            $this->setData($id, ['offset' => $offset + 5, 'finish' => false]);
        }

        return $items;
    }
}

