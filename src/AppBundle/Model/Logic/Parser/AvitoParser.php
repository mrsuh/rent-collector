<?php

namespace AppBundle\Model\Logic\Parser;

use AppBundle\Exception\ParseException;
use AppBundle\Model\Logic\Explorer\Subway\SubwayExplorer;
use PHPHtmlParser\Dom;
use Schema\Note\Photo;
use Schema\Note\Note;
use Schema\Parse\Record\Source;


class AvitoParser implements Parser
{
    /**
     * @var Dom
     */
    protected $dom;

    /**
     * @var SubwayExplorer
     */
    private $explorer_subway;

    /**
     * @var Source
     */
    private $source;

    /**
     * AvitoParser constructor.
     * @param                $data
     * @param Source         $source
     * @param SubwayExplorer $explorer_subway
     * @throws ParseException
     */
    public function __construct($data, Source $source, SubwayExplorer $explorer_subway)
    {
        switch (true) {
            case $data instanceof Dom:
            case $data instanceof Dom\HtmlNode:
                $this->dom = $data;
                break;
            case is_string($data):
                $this->dom = new Dom();
                $this->dom->load($data);
                break;
            default:
                throw new ParseException(sprintf('%s: Data is not an instance of %s or %s or string', __CLASS__ . '/' . __FUNCTION__, Dom::class, Dom\HtmlNode::class));
        }

        $this->source          = $source;
        $this->explorer_subway = $explorer_subway;
    }

    /**
     * @return mixed|string
     */
    public function contactId()
    {
        $id = str_replace('.', '-', uniqid('av', true));

        $link = $this->dom->find('.person-name');

        if (null === $link) {

            return $id;
        }

        preg_match('/\/user\/(.*)\/profile/', $link->href, $match);

        if (array_key_exists(1, $match) && !empty($match[1])) {
            $id = 'av' . $match[1];
        }

        return $id;
    }

    public function contactName(string $id = '')
    {
        $elems = $this->dom->find('.person-name');

        if (array_key_exists(0, $elems)) {
            throw new ParseException('Array of elems has not key "0"');
        }

        $elem = $elems[0];

        return (string)$elem->text;
    }

    /**
     * @return int
     */
    public function timestamp()
    {
        $elems = $this->dom->find('.info-date');

        $elem = $elems[0];

        $date = new \DateTime('now', new \DateTimeZone('Europe/Moscow'));
        $text = mb_strtolower($elem->text);
        switch (true) {
            case mb_strrpos($text, 'сегодня'):

                preg_match('/(\d+)\:(\d+)/', $text, $match);
                $hour    = $match[1];
                $minutes = $match[2];

                $date->setTime($hour, $minutes);
                break;
            case mb_strrpos($text, 'вчера'):
                preg_match('/(\d+)\:(\d+)/', $text, $match);
                $hour    = $match[1];
                $minutes = $match[2];

                $date->modify('- 1 day');
                $date->setTime($hour, $minutes);
                break;
            default:

                preg_match('/(\d+)([\D]+)(\d+)\:(\d+)/', $text, $match);

                $day    = $match[1];
                $month  = $this->getMonthNumberByStr($match[2]);
                $hour   = $match[3];
                $minute = $match[4];

                $date->setDate(\Date('Y'), $month, $day);
                $date->setTime($hour, $minute);
                break;
        }

        $date->setTimezone(new \DateTimeZone('UTC'));
        return $date->getTimestamp() + $date->getOffset();
    }

    /**
     * @return string
     */
    public function description()
    {
        $elems = $this->dom->find('.description-preview-wrapper p');

        $description = '';
        foreach ($elems as $elem) {
            $description .= $elem->text . PHP_EOL;
        }

        return $description;
    }

    public function id()
    {
        return '';
    }

    public function link(string $id = '')
    {
        return '';
    }

    public function phones()
    {
        return [];
    }

    public function photos()
    {
        $elems = $this->dom->find('[property="og:image"]');

        $photos = [];
        foreach ($elems as $elem) {

            $link = $elem->content;

            $low  = $link;
            $high = str_replace('640x480', '1280x960', $link);

            $photos[] =
                (new Photo())
                    ->setLow($low)
                    ->setHigh($high);
        }

        return $photos;
    }

    public function price()
    {
        $elems = $this->dom->find('.price-value');

        $elem = $elems[0];

        $price_str = mb_strtolower($elem->text);

        preg_match('/^([\d\s]+)/', $price_str, $match);

        $number = str_replace(' ', '', $match[1]);

        switch (true) {
            case false !== mb_strrpos('месяц', $price_str):
                $price = $number;
                break;
            case false !== mb_strrpos('сут', $price_str):
                $price = $number * 30;
                break;
            case false !== mb_strrpos('недел', $price_str):
                $price = $number * 4;
                break;
            default:
                $price = $number;
        }

        return (int)$price;
    }

    public function subways()
    {
        $subways = $this->dom->find('.avito-address-text');

        $subways_str = $subways[0];

        return $this->explorer_subway->explore($subways_str);
    }

    public function type()
    {
        $elems = $this->dom->find('.single-item-header .text');
        $elem  = $elems[1];

        $type_str = mb_strtolower($elem->text);

        $type = Note::TYPE_ERR;
        switch (true) {
            case false !== mb_strrpos($type_str, '1-к') && false !== mb_strrpos($type_str, 'кварт'):
                $type = Note::TYPE_FLAT_1;
                break;
            case false !== mb_strrpos($type_str, '2-к') && false !== mb_strrpos($type_str, 'кварт'):
                $type = Note::TYPE_FLAT_2;
                break;
            case false !== mb_strrpos($type_str, '3-к') && false !== mb_strrpos($type_str, 'кварт'):
                $type = Note::TYPE_FLAT_3;
                break;
            case false !== mb_strrpos($type_str, '4-к') && false !== mb_strrpos($type_str, 'кварт'):
                $type = Note::TYPE_FLAT_N;
                break;
            case false !== mb_strrpos($type_str, 'комн'):
                $type = Note::TYPE_ROOM;
                break;
            case false !== mb_strrpos($type_str, 'студ'):
                $type = Note::TYPE_STUDIO;
                break;
        }

        return $type;
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
