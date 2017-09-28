<?php

namespace AppBundle\Request;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class AvitoRequest
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
     * @var array
     */
    private $headers;

    /**
     * VkPublicRequest constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->url    = 'https://www.avito.ru/';

        $this->headers = [
            'accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'accept-encoding'           => 'gzip, deflate, sdch, br',
            'accept-language'           => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            'cache-control'             => 'max-age=0',
            'referer'                   => 'https://www.avito.ru/',
            'upgrade-insecure-requests' => '1',
            'user-agent'                => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36',
            'cookie'                    => 'u=26isga9z.1gac0ni.fjwbjucs2z;'
        ];
    }

    /**
     * @param string $url
     * @return Response
     */
    public function getList(string $url, int $page): Response
    {
        $query = [
            'user' => 1,
            'view' => 'list',
            's'    => 104, //order by date
            'page' => $page
        ];

        return $this->client->send(new Request('GET', $this->url . $url, $this->headers), ['query' => $query]);
    }

    /**
     * @param string $url
     * @return Response
     */
    public function getRecord(string $url): Response
    {
        return $this->client->send(new Request('GET', $this->url . $url, $this->headers));
    }
}