<?php

namespace AppBundle\Model\Logic\Explorer\Tomita;

use AppBundle\Exception\ExploreException;
use AppBundle\Request\TomitaRequest;

class TomitaExplorer
{
    /**
     * @var TomitaRequest
     */
    private $request;

    /**
     * @var Tomita[]
     */
    private $cache;

    /**
     * TomitaService constructor.
     * @param TomitaRequest $request
     */
    public function __construct(TomitaRequest $request)
    {
        $this->request = $request;
        $this->cache   = [];
    }

    /**
     * @param string $text
     * @return Tomita
     * @throws ExploreException
     */
    public function explore(string $text)
    {
        $key = md5($text);

        if (!array_key_exists($key, $this->cache)) {

            $this->cache[$key] = $this->exploreTomita($text);
        }

        return $this->cache[$key];
    }

    /**
     * @param string $text
     * @return Tomita
     * @throws ExploreException
     */
    private function exploreTomita(string $text)
    {
        $response = $this->request->parse($text);

        $content = $response->getBody()->getContents();

        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new ExploreException('Response has invalid json');
        }

        foreach (['type', 'price'] as $key) {
            if (!array_key_exists($key, $data)) {
                throw new ExploreException((sprintf('Has not key "%s" in response', $key)));
            }
        }

        return
            (new Tomita())
                ->setType($data['type'])
                ->setPrice($data['price'])
                ->setPhones([]);
    }
}

