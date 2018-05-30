<?php

namespace AppBundle\Request;

use AppBundle\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class VkPublicRequest
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $url;

    /**
     * @var float
     */
    private $version;

    /**
     * @var string
     */
    private $token;

    /**
     * VkPublicRequest constructor.
     * @param Client $client
     */
    public function __construct(Client $client, string $token)
    {
        $this->client  = $client;
        $this->url     = 'https://api.vk.com/method';
        $this->version = 5.64;
        $this->token   = $token;
    }

    /**
     * @param array $data
     * @return Response
     */
    public function getCommentRecords(array $data): Response
    {
        $query = [
            'group_id'         => $data['group_id'],
            'topic_id'         => $data['topic_id'],
            'count'            => $data['count'],
            'start_comment_id' => $data['start_comment_id'],
            'v'                => $this->version,
            'access_token'     => $this->token
        ];

        return $this->client->send(new Request('GET', $this->url . '/board.getComments'), ['query' => $query]);
    }

    /**
     * @param array $data
     * @return Response
     * @throws RequestException
     */
    public function getWallRecords(array $data): Response
    {
        $query = [
            'owner_id'     => $data['owner_id'],
            'count'        => $data['count'],
            'offset'       => $data['offset'],
            'v'            => $this->version,
            'access_token' => $this->token
        ];

        return $this->client->send(new Request('GET', $this->url . '/wall.get'), ['query' => $query]);
    }

    /**
     * @param array $data
     * @return Response
     */
    public function getMarketRecords(array $data): Response
    {
        $data['v']            = $this->version;
        $data['access_token'] = $this->token;

        return $this->client->send(new Request('GET', $this->url . '/market.get'), ['query' => $data]);
    }

    /**
     * @param int $user_id
     * @return Response
     */
    public function getUserInfo(int $user_id): Response
    {
        $query = [
            'user_ids'     => $user_id,
            'fields'       => 'blacklisted',
            'v'            => $this->version,
            'access_token' => $this->token
        ];

        return $this->client->send(new Request('GET', $this->url . '/users.get'), ['query' => $query]);
    }
}