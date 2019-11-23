<?php

namespace App\Request;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class TomitaRequest
{
    protected $client;
    protected $url;

    public function __construct(Client $client, string $parserUrl)
    {
        $this->client = $client;
        $this->url    = $parserUrl;
    }

    public function parse(string $text): ?ResponseInterface
    {
        return $this->client->send(new Request('POST', $this->url . '/parse'), ['body' => $text]);
    }
}