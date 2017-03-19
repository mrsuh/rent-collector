<?php

namespace AppBundle\Model\Parser\Contact;

use AppBundle\Service\TomitaService;

class TextContactParser
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
     * @return array
     */
    public function parseText($text)
    {
        $text = $this->normalize($text);
        $xml  = $this->tomita->run($text);

        $contacts = $this->getByXML($xml);

        return $contacts;
    }

    /**
     * @param $out
     * @return array
     */
    private function getByXML($out)
    {
        $contacts = [];

        if (null === $out) {

            return $contacts;
        }

        $xml = simplexml_load_string($out);

        if (count($xml->document->facts) === 0) {

            return $contacts;
        }

        foreach ($xml->document->facts->FactContact as $f) {
            $phone      = (string)$f->Contact['val'];
            $contacts[] = preg_replace('/(\d|)(\d{10}$)/', '$2', $phone);
        }

        return $contacts;
    }

    /**
     * @param $text
     * @return mixed|string
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


        $text = preg_replace('/[+\(\)-]/ui', '', $text);
        $text = preg_replace('/(\d)\s(\d)/ui', '$1$2', $text);

        $text = preg_replace('/([=\+.\!?])(\d)/ui', "$1 $2", $text);
        $text = preg_replace('/(\d)([=\+.\!?])/ui', "$1 $2", $text);


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
}

