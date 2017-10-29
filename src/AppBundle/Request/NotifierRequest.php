<?php

namespace AppBundle\Request;

use GuzzleHttp\Psr7\Request;
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
    public function notify(Note $note)
    {
        $photos = [];
        foreach ($note->getPhotos() as $photo) {
            $photos[] = $photo->getHigh();
        }

        $body = [
            'description' => $note->getDescription(),
            'subways'     => $note->getSubways(),
            'price'       => $note->getPrice(),
            'type'        => $note->getType(),
            'link'        => $note->getLink(),
            'photos'      => $photos,
            'city'        => $note->getCity(),
            'contact'     => $note->getContact(),
        ];

        return $this->client->send(new Request('POST', $this->url . '/notify'), ['body' => json_encode($body)]);
    }
}