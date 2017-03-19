<?php

namespace AppBundle\Model\Normalizer;

use AppBundle\Exception\RequestException;
use AppBundle\Service\Client\Http;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\Validator\Exception\ValidatorException;

class AddressNormalizer
{
    private $client;

    public function __construct(Http $client)
    {
        $this->client = $client;
    }

    public function normalize($text)
    {
        $data = [
            'query' => [
                'geocode' => $text,
                'format'  => 'json'
            ]
        ];

        try {

            $response = $this->client->send(new Request('GET', 'https://geocode-maps.yandex.ru/1.x'), $data);

        } catch (RequestException $e) {
            throw $e;
        }

        $json = $response->getBody()->getContents();

        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new ValidatorException('Response has invalid JSON');
        }

        file_put_contents('response.json', $json);

        return $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['name'];
    }
}