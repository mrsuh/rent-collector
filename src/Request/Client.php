<?php

namespace App\Request;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Client
{
    protected $client;
    protected $logger;

    public function __construct(LoggerInterface $logger, int $httpClientTimeout, int $httpClientConnectTimeout)
    {
        $this->logger = $logger;
        $this->client = new \GuzzleHttp\Client([
            'timeout'         => $httpClientTimeout,
            'connect_timeout' => $httpClientConnectTimeout,
            'redirect_allow'  => true,
            'cookies'         => true
        ]);
    }

    public function send(Request $request, array $data = []): ?ResponseInterface
    {
        $this->logger->debug('HTTP Request', [
            'method' => $request->getMethod(),
            'uri'    => (string)$request->getUri(),
            'params' => $data
        ]);

        $response = $this->client->send($request, $data);

        $this->logger->debug('HTTP Response', [
            'method'       => $request->getMethod(),
            'uri'          => (string)$request->getUri(),
            'params'       => $data,
            'responseCode' => $response->getStatusCode()
        ]);

        if (!$response instanceof ResponseInterface) {
            return null;
        }

        return $response;
    }
}