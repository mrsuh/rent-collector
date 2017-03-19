<?php

namespace AppBundle\Model\Parser\Area;

use AppBundle\Service\TomitaService;

class TextAreaParser
{
    private $tomita;

    /**
     * TextAreaParser constructor.
     * @param TomitaService $tomita
     */
    public function __construct(TomitaService $tomita)
    {
        $this->tomita = $tomita;
    }

    /**
     * @param $text
     * @return mixed|null
     */
    public function parseText($text)
    {
        $text = $this->normalize($text);

        $xml = $this->tomita->run($text);

        $area = $this->getByXML($xml);

        return array_key_exists(0, $area) ? (float)$area[0] : null;
    }

    /**
     * @param $text
     * @return string
     */
    public function normalize($text)
    {
        $text = mb_strtolower($text);

        if (1 === preg_match('/\?\W{0,10}$/u', $text)) {
            $text = '';
        }

        $text = preg_replace('/публиковать.*/ui', '', $text);
        $text = preg_replace('/правила темы.*/ui', '', $text);

        $text = str_replace('\n', PHP_EOL, $text);


        $text = preg_replace('/([\d=\+.\!?])([а-яеёa-z])/ui', "$1 $2", $text);
        $text = preg_replace('/([а-яеёa-z])([\d=\+.\!?])/ui', "$1 $2", $text);


        $text = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]' .
            '|[\x00-\x7F][\x80-\xBF]+' .
            '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*' .
            '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})' .
            '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
            '', $text);

        $text = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]' .
            '|\xED[\xA0-\xBF][\x80-\xBF]/S', '', $text);


        return $text;
    }

    /**
     * @param $out
     * @return array
     */
    private function getByXML($out)
    {
        $streets = [];

        if (null === $out) {

            return $streets;
        }

        $xml = simplexml_load_string($out);

        if (count($xml->document->facts) === 0) {

            return $streets;
        }

        foreach ($xml->document->facts->FactArea as $f) {
            $streets[] = (string)$f->Area['val'];
        }

        return $streets;
    }
}

