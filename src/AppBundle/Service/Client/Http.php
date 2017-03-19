<?php

namespace AppBundle\Service\Client;

use AppBundle\Exception\RequestException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;

class Http
{
    protected $client;

    public function __construct(array $guzzle)
    {
        $headers = [
            'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding'           => 'gzip, deflate, sdch',
            'Accept-Language'           => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            'Connection'                => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent'                => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/53.0.2785.143 Chrome/53.0.2785.143 Safari/537.36',
        ];

        $this->client = new Client([
            'timeout'         => $guzzle['timeout'],
            'connect_timeout' => $guzzle['connect_timeout'],
            'redirect_allow'  => true,
            'cookies'         => true,
            'headers'         => $headers
        ]);
    }

    public function send(Request $request, $data = [])
    {
        try {
            $response = $this->client->send($request, $data);
        } catch (\Exception $e) {
            if ($e instanceof ClientException || $e instanceof ServerException) {
                throw (new RequestException($e->getMessage(), null, $e))
                    ->setResponse($e->getResponse())
                    ->setRequest($request)
                    ->setParameters($data);
            }

            throw new RequestException($e->getMessage(), null, $e);
        }

        return $response;
    }
}