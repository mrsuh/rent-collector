<?php

namespace AppBundle\Model\Explorer\Contact;

use AppBundle\Exception\ExploreException;
use AppBundle\Service\Client\Http;
use GuzzleHttp\Psr7\Request;

class VkWallContactExplorer
{
    private $http_client;

    public function __construct(Http $http_client)
    {
        $this->http_client = $http_client;
    }

    public function explore(array $data)
    {
        switch (true) {
            case array_key_exists('signer_id', $data):
                $user_id = $data['signer_id'];
                break;
            case array_key_exists('from_id', $data):
                $user_id = $data['from_id'];
                break;
            default:
                $user_id = null;
                break;
        }

        if (null === $user_id) {
            return null;
        }

        $params = [
            'user_ids' => [$user_id],
            'v'        => 5.62
        ];

        usleep(200);
        $response = $this->http_client->send(new Request('GET', 'https://api.vk.com/method/users.get'), ['query' => $params]);

        $contents = $response->getBody()->getContents();

        $info = json_decode($contents, true);

        if (!array_key_exists('response', $info)) {
            throw new ExploreException('Has not key "response" in response');
        }

        $user = null;
        foreach ($info['response'] as $i) {
            switch (true) {
                case array_key_exists('id', $i):
                    $id = (string)$i['id'];
                    break;
                case array_key_exists('uid', $i):
                    $id = (string)$i['uid'];
                    break;
                default:
                    $id = null;
            }

            if ($id === (string)$user_id) {
                $user = $i;
                break;
            }
        }

        return $user['first_name'] . ' ' . $user['last_name'];
    }
}