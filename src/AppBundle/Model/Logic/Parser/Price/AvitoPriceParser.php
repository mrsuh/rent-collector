<?php

namespace AppBundle\Model\Logic\Parser\Price;

use AppBundle\Exception\ParseException;
use PHPHtmlParser\Dom;

class AvitoPriceParser implements PriceParserInterface
{
    /**
     * @param $data
     * @return int
     * @throws ParseException
     */
    public function parse($data): int
    {
        if (!($data instanceof Dom)) {
            throw new ParseException(sprintf('%s: data is not an instance of %s', __CLASS__ . '\\' . __FUNCTION__, Dom::class));
        }

        $elems = $data->find('.price-value-string');

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
}
