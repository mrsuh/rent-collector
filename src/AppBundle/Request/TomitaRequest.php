<?php

namespace AppBundle\Request;

use GuzzleHttp\Psr7\Request;

class TomitaRequest
{
    protected $client;

    /**
     * TomitaRequest constructor.
     * @param Client $client
     * @param string $url
     */
    public function __construct(Client $client, string $url)
    {
        $this->client = $client;
        $this->url    = $url;
    }

    /**
     * @param string $text
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function parse(string $text)
    {
        return $this->client->send(new Request('POST', $this->url . '/parse'), ['body' => $text]);
    }
}