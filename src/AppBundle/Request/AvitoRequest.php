<?php

namespace AppBundle\Request;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;

class AvitoRequest
{
    /**
     * @var Proxy
     */
    private $client;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $proxy;

    /**
     * AvitoRequest constructor.
     * @param Proxy  $client
     * @param Logger $logger
     * @param string $proxy
     */
    public function __construct(Proxy $client, Logger $logger, string $proxy)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->url    = 'https://m.avito.ru/';

        $this->proxy = $proxy;
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
            'i'    => 1, //photo only
            'page' => $page
        ];

        $fullUrl = sprintf('%s%s?%s', $this->url, $url, http_build_query($query));

        $this->logger->debug("Request " . $fullUrl);

        return $this->client->send(new Request('GET', $this->proxy, [
            'X-Proxy-Url'                 => $fullUrl,
            'X-Proxy-Attempts'            => 20,
            'X-Proxy-Content-Fail-Phrase' => 'временно заблокировать доступ'
        ]));
    }

    /**
     * @param string $url
     * @return Response
     */
    public function getRecord(string $url): Response
    {
        $fullUrl = sprintf('%s%s', $this->url, $url);

        $this->logger->debug("Request " . $fullUrl);

        return $this->client->send(new Request('GET', $this->proxy, [
            'X-Proxy-Url'                 => $fullUrl,
            'X-Proxy-Attempts'            => 20,
            'X-Proxy-Content-Fail-Phrase' => 'временно заблокировать доступ'
        ]));
    }
}