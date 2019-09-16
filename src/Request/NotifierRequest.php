<?php

namespace App\Request;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Schema\City\City;
use Schema\Note\Note;

class NotifierRequest
{
    protected $client;
    protected $url;

    public function __construct(Client $client, string $notifierUrl)
    {
        $this->client = $client;
        $this->url    = $notifierUrl;
    }

    public function notify(City $city, Note $note): ?ResponseInterface
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
            'contact'     => $note->getContact()->getName(),
            'source'      => $note->getSource()
        ];

        return $this->client->send(new Request('POST', $this->url . '/notify'), ['body' => json_encode($body)]);
    }
}