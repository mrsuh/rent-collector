<?php

namespace AppBundle\Request;

use GuzzleHttp\Psr7\Request;
use Schema\City\City;
use Schema\Note\Note;

class NotifierRequest
{
    protected $client;
    protected $url;

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
     * @param Note $note
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function notify(City $city, Note $note)
    {
        $photos = [];
        foreach ($note->getPhotos() as $photo) {
            $photos[] = $photo->getHigh();
        }

        $subways = [];
        foreach ($note->getSubways() as $subway) {
            $subways[] = (int)$subway;
        }

        $body = [
            'description' => $note->getDescription(),
            'subways'     => $subways,
            'price'       => (int)$note->getPrice(),
            'type'        => (int)$note->getType(),
            'link'        => $note->getLink(),
            'photos'      => $photos,
            'city'        => (int)$city->getId(),
            'contact'     => $note->getContact(),
        ];

        return $this->client->send(new Request('POST', $this->url . '/notify'), ['body' => json_encode($body)]);
    }
}