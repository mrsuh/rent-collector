<?php

namespace AppBundle\Request;

use AppBundle\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;

class Proxy
{
    protected $client;

    /**
     * Client constructor.
     * @param array $guzzle
     */
    public function __construct(array $guzzle)
    {
        $this->client = new \GuzzleHttp\Client([
            'timeout'         => $guzzle['proxy_timeout'],
            'connect_timeout' => $guzzle['proxy_connect_timeout'],
            'redirect_allow'  => true,
            'cookies'         => true
        ]);
    }

    /**
     * @param Request $request
     * @param array   $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws RequestException
     */
    public function send(Request $request, array $data = [])
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