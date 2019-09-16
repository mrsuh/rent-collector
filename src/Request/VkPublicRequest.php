<?php

namespace App\Request;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class VkPublicRequest
{
    private $client;
    private $url;
    private $version;
    private $token;

    public function __construct(Client $client, string $vkAppToken)
    {
        $this->client  = $client;
        $this->url     = 'https://api.vk.com/method';
        $this->version = 5.64;
        $this->token   = $vkAppToken;
    }

    public function getCommentRecords(string $groupId, string $topicId, int $count, int $offset): ?ResponseInterface
    {
        $query = [
            'group_id'     => $groupId,
            'topic_id'     => $topicId,
            'count'        => $count,
            'sort'         => 'desc',
            'offset'       => $offset,
            'v'            => $this->version,
            'access_token' => $this->token
        ];

        return $this->client->send(new Request('GET', $this->url . '/board.getComments'), ['query' => $query]);
    }

    public function getWallRecords(string $ownerId, int $count, int $offset): ?ResponseInterface
    {
        $query = [
            'owner_id'     => $ownerId,
            'count'        => $count,
            'offset'       => $offset,
            'v'            => $this->version,
            'access_token' => $this->token
        ];

        return $this->client->send(new Request('GET', $this->url . '/wall.get'), ['query' => $query]);
    }
}