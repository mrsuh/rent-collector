<?php

namespace AppBundle\Model\Logic\Parser\DateTime;

use AppBundle\Exception\ParseException;
use PHPHtmlParser\Dom\HtmlNode;

class AvitoDateTimeParser implements DateTimeParserInterface
{
    /**
     * @param mixed $data
     * @return int
     * @throws ParseException
     */
    public function parse($data): int
    {
        if (!($data instanceof HtmlNode)) {
            throw new ParseException(sprintf('Data is not an instance of %s', HtmlNode::class));
        }

        $elems = $data->find('.info-date');

        $elem = $elems[0];

        $text = mb_strtolower($elem->text);


        switch (true) {
            case mb_strrpos($text, 'сегодня'):

                preg_match('/(\d+)\:(\d+)/', $text, $match);
                $hour    = $match[1];
                $minutes = $match[2];

                $now = new \DateTime();

                $now->setTime($hour, $minutes);

                $timestamp = $now->getTimestamp();

                break;
            case mb_strrpos($text, 'вчера'):
                preg_match('/(\d+)\:(\d+)/', $text, $match);
                $hour    = $match[1];
                $minutes = $match[2];

                $now = new \DateTime();
                $now->modify('- 1 day');

                $now->setTime($hour, $minutes);

                $timestamp = $now->getTimestamp();

                break;
            default:

                preg_match('/(\d+)([\D]+)(\d+)\:(\d+)/', $text, $match);

                $number = $match[1];
                $month  = $this->getMonthNumberByStr($match[2]);
                $hour   = $match[3];
                $minute = $match[4];

                $date = \DateTime::createFromFormat('m-d H:i', sprintf('%s-%s %s:%s', $month, $number, $hour, $minute));

                $timestamp = $date->getTimestamp();

                break;
        }

        return $timestamp;
    }

    private function getMonthNumberByStr(string $str)
    {
        $month = 1;

        $str = mb_strtolower($str);

        switch (true) {
            case false !== mb_strrpos($str, 'январ'):
                $month = 1;
                break;
            case false !== mb_strrpos($str, 'феврал'):
                $month = 2;
                break;
            case false !== mb_strrpos($str, 'март'):
                $month = 3;
                break;
            case false !== mb_strrpos($str, 'апрел'):
                $month = 4;
                break;
            case false !== mb_strrpos($str, 'ма'):
                $month = 5;
                break;
            case false !== mb_strrpos($str, 'июн'):
                $month = 6;
                break;
            case false !== mb_strrpos($str, 'июл'):
                $month = 7;
                break;
            case false !== mb_strrpos($str, 'авгу'):
                $month = 8;
                break;
            case false !== mb_strrpos($str, 'сентя'):
                $month = 9;
                break;
            case false !== mb_strrpos($str, 'октяб'):
                $month = 10;
                break;
            case false !== mb_strrpos($str, 'нояб'):
                $month = 11;
                break;
            case false !== mb_strrpos($str, 'декаб'):
                $month = 12;
                break;
        }

        return $month;
    }
}

