<?php

namespace AppBundle\Model\Parser\Type;

use AppBundle\Service\TomitaService;

class TextTypeParser
{
    private $tomita;

    /**
     * TextTypeParser constructor.
     * @param TomitaService $tomita
     */
    public function __construct(TomitaService $tomita)
    {
        $this->tomita = $tomita;
    }

    /**
     * @param $text
     * @return int
     */
    public function parseText($text)
    {
        $text = $this->normalize($text);

        $xml = $this->tomita->run($text);

        return (int)$this->getTypeByXML($xml);
    }

    /**
     * @param $text
     * @return string
     */
    private function normalize($text)
    {
        if (1 === preg_match('/\?\W{0,10}$/u', $text)) {
            $text = '';
        }

        $text = preg_replace('/(публиковать|Некоторые варианты квартир с нашего сайта|правила темы|сайт)(.|\n)*/ui', '', $text);
        $text = preg_replace('/http(s):(\w|\/|\.)*/ui', '', $text);

        $text = str_replace('\n', PHP_EOL, $text);

        $text = mb_substr($text, 0, 500);

        $months = [
            'январ',
            'феврал',
            'март',
            'апрел',
            'май',
            'июн',
            'июл',
            'август',
            'сентябр',
            'октябр',
            'ноябр',
            'декабр',
        ];

        foreach ($months as $month) {
            $text = preg_replace('/\d{0,2}[^0-9\.\!\?;]{0,3}' . $month . '[а-я]{0,4}/ui', '', $text);
        }

        $flat = [
            '/(\s|[0-9])кк(\s|\.)/ui',
            '/(\s|[0-9])ккв(\s|\.)/ui',
            '/(\s|[0-9])к\.к(\s|\.)/ui',
            '/(\s|[0-9])к\.квартира(\s|\.)/ui',
            '/(\s|[0-9])к\.квартиру(\s|\.)/ui',
            '/(\s|[0-9])к\.кв(\s|\.)/ui',
            '/(\s|[0-9])к\.кварт(\s|\.)/ui',
            '/(\s|[0-9])комн\.кв(\s|\.)/ui',
            '/(\s|[0-9])хкк(\s|\.)/ui'
        ];

        $text = preg_replace($flat, "$1 комнатная квартира ", $text);

        $text = preg_replace('/\d{1,3}\s{0,10}(кв(\.|\s){0,1}м(\.|\s){0,1}|м²|м(\.|\s))/ui', ' ', $text);

        $text = preg_replace('/([\d-=\+.\!?])([а-яеёa-z])/ui', "$1 $2", $text);
        $text = preg_replace('/([а-яеёa-z])([\d-=\+.\!?])/ui', "$1 $2", $text);

        $text = preg_replace('/\sквартир[а-яА-Яeё]*/ui', " квартира ", $text);
        $text = preg_replace('/\sкомната[а-яА-Яeё]*/ui', " комната ", $text);

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
     * @return int
     */
    private function getTypeByXML($out)
    {
        if (null === $out) {

            return 6;
        }

        $xml = simplexml_load_string($out);

        if (count($xml->document->facts) === 0) {

            return 6;
        }

        foreach ($xml->document->facts->FactError as $f) {

            return 6;
        }

        $wrong = false;
        foreach ($xml->document->facts->FactWrong as $f) {
            $wrong = true;
            break;
        }

        $realty = null;
        $length = 0;
        foreach ($xml->document->facts->FactRealty as $f) {
            $str = (string)$f->Type['val'];
            $l   = mb_strlen($str);

            if ($l > $length) {
                $realty = $str;
                $length = $l;
            }

            if (5 === $this->getTypeByString($str)) {
                $realty = $str;
                break;
            }
        }

        $rent      = null;
        $length    = 0;
        $rent_type = 0;
        foreach ($xml->document->facts->FactRent as $f) {
            $str = (string)$f->Type['val'];
            $l   = mb_strlen($str);

            if ($l > $length) {
                $rent   = $str;
                $length = $l;
            }


            $type = $this->getTypeByString($str);

            if (5 === $type) {
                $rent = $str;
                break;
            }

            if (0 === $type) {
                $rent = $str;
                break;
            }

            if ($type > $rent_type) {
                $rent      = $str;
                $rent_type = $type;
            }
        }

        $neighbor = null;
        $length   = 0;
        foreach ($xml->document->facts->FactNeighbor as $f) {
            $str = (string)$f->Type['val'];
            $l   = mb_strlen($str);

            if ($l > $length) {
                $neighbor = $str;
                $length   = $l;
            }

            if (5 === $this->getTypeByString($str)) {
                $neighbor = $str;
                break;
            }
        }

        switch (true) {
            case null !== $rent:
                $type = $this->getTypeByString($rent);
                break;
            case null !== $neighbor:
                $type = $this->getTypeByString($neighbor);
                break;
            case $wrong:
                $type = 6;
                break;
            case null !== $realty:
                $type = $this->getTypeByString($realty);
                break;
            default:
                $type = 6;
        }

        return $type;

    }

    /**
     * @param $str
     * @return int
     */
    private function getTypeByString($str)
    {
        $raw = mb_strtolower($str);
        switch (true) {
            case 1 === preg_match('/студи/', $raw):
                $type = 5;
                break;
            case 1 === preg_match('/(^|\W)комнаты($|\W)/', $raw):
                $type = 0;
                break;
            case 1 === preg_match('/1/', $raw):
                $type = 1;
                break;
            case 1 === preg_match('/2/', $raw):
                $type = 2;
                break;
            case 1 === preg_match('/3/', $raw):
                $type = 3;
                break;
            case 1 === preg_match('/(([^\d,\.!?]|^)[4-9]\D{0,30}квартир|четыр\Sх|много)|(квартир\D{0,3}1\D.{0,10}комнатн)/', $raw):
                $type = 4;
                break;
            case 1 === preg_match('/(^|\W)квартир\W{1,4}($|\W)/', $raw):
                $type = 1;
                break;
            case 1 === preg_match('/(^|\W)комнат/', $raw):
                $type = 0;
                break;
            default:
                echo 'DEF WRONG ' . $str . PHP_EOL;
                $type = 6;
        }

        return $type;
    }
}

