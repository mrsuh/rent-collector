<?php

namespace AppBundle\Request;

use AppBundle\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Schema\Parse\App\App;

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
     * @var App
     */
    private $app;

    /**
     * VkPublicRequest constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client  = $client;
        $this->url     = 'https://api.vk.com/method';
        $this->version = 5.64;
    }

    public function setApp(App $app)
    {
        $this->app = $app;

        return true;
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
     * @throws RequestException
     */
    public function getWallRecords(array $data): Response
    {
        $data['v'] = $this->version;
        if (null === $this->app) {

            throw new RequestException('There is no app');
        }

        $data['access_token'] = $this->app->getToken();

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