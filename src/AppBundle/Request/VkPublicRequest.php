<?php

namespace AppBundle\Request;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class VkPublicRequest
{
    private $client;
    private $url;
    private $version;

    /**
     * VkPublicRequest constructor.
     * @param Client $client
     * @param string $url
     */
    public function __construct(Client $client, string $url, float $version)
    {
        $this->client  = $client;
        $this->url     = $url;
        $this->version = $version;
    }

    /**
     * @param array $data
     * @return Response
     */
    public function getCommentRecords(array $data): Response
    {
        $data['v'] = $this->version;

        return $this->client->send(new Request('GET', $this->url . '/board.getComments'), ['query' => $data]);
    }

    /**
     * @param array $data
     * @return Response
     */
    public function getWallRecords(array $data): Response
    {
        $data['v'] = $this->version;

        return $this->client->send(new Request('GET', $this->url . '/wall.get'), ['query' => $data]);
    }

    /**
     * @param array $data
     * @return Response
     */
    public function getMarketRecords(array $data): Response
    {
        $data['v'] = $this->version;

        return $this->client->send(new Request('GET', $this->url . '/market.get'), ['query' => $data]);
    }

    /**
     * @param int $user_id
     * @return Response
     */
    public function getUserInfo(int $user_id): Response
    {
        $data = [
            'query' => [
                'user_ids' => $user_id,
                'fields'   => 'photo_100',
                'v'        => $this->version
            ]
        ];

        return $this->client->send(new Request('GET', $this->url . '/users.get'), $data);
    }
}