<?php

namespace AppBundle\Model\Explorer\Tomita;

use AppBundle\Exception\ExploreException;
use AppBundle\Request\TomitaRequest;

class TomitaExplorer
{
    private $request;

    /**
     * TomitaExplorer constructor.
     * @param TomitaRequest $request
     */
    public function __construct(TomitaRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @param string $text
     * @return Tomita
     * @throws ExploreException
     */
    public function explore(string $text): Tomita
    {
        $response = $this->request->parse($text);

        $data = json_decode($response->getBody()->getContents(), true);

        foreach (['type', 'phone', 'area', 'price'] as $key) {
            if (!array_key_exists($key, $data)) {
                throw new ExploreException((sprintf('Has not key "%s" in response', $key)));
            }
        }

        return (new Tomita())
            ->setType($data['type'])
            ->setPrice($data['price'])
            ->setArea($data['area'])
            ->setPhones($data['phone']);

    }
}

