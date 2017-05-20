<?php

namespace AppBundle\Model\Collector;

use AppBundle\Exception\ParseException;
use AppBundle\Request\VkRequest;
use AppBundle\Storage\FileStorage;

class VkWallCollector implements CollectorInterface
{
    private $request;
    private $storage;

    /**
     * VkWallCollector constructor.
     * @param VkRequest $request
     * @param string    $file_dir
     */
    public function __construct(VkRequest $request, string $file_dir)
    {
        $this->request = $request;
        $this->storage = new FileStorage($file_dir);
    }

    /**
     * @param string $file_name
     * @return array
     */
    private function getData(string $file_name): array
    {
        return $this->storage->exists($file_name) ? $this->storage->get($file_name) : ['offset' => 0, 'finish' => false];
    }

    /**
     * @param string $file_name
     * @param        $data
     * @return bool
     */
    private function setData(string $file_name, $data): bool
    {
        return $this->storage->put($file_name, $data);
    }

    /**
     * @param array $config
     * @param bool  $debug
     * @return array
     * @throws ParseException
     */
    public function collect(array $config, bool $debug = false): array
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
        $response = $this->request->getWallRecords($params);

        $contents = $response->getBody()->getContents();

        $data = json_decode($contents, true);

        if (!array_key_exists('response', $data)) {
            throw new ParseException('Has not key "response" in response');
        }

        if (!array_key_exists('items', $data['response'])) {
            throw new ParseException('Has not key "items" in response');
        }

        $items_raw = $data['response']['items'];
        $items     = [];
        $finish = false;
        foreach ($items_raw as $item) {

            $is_pinned     = array_key_exists('is_pinned', $item) && $item['is_pinned'];
            $market_as_ads = array_key_exists('marked_as_ads', $item) && $item['marked_as_ads'];

            if ($market_as_ads) {
                continue;
            }

            if ($timestamp > $item['date'] && $is_pinned) {
                continue;
            }

            if ($timestamp > $item['date']) {
                $finish = true;
                break;
            }

            $items[] = $item;
        }

        if ($finish) {
            $this->setData($id, ['offset' => 0, 'finish' => true]);
        } else {
            $this->setData($id, ['offset' => $offset + 5, 'finish' => false]);
        }

        return $items;
    }
}

