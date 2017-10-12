<?php

namespace AppBundle\Model\Logic\Parser\ContactId;

use AppBundle\Exception\ParseException;
use PHPHtmlParser\Dom;

class AvitoContactIdParser implements ContactIdParserInterface
{
    /**
     * @param $data
     * @return string
     * @throws ParseException
     */
    public function parse($data): string
    {
        if (!($data instanceof Dom)) {
            throw new ParseException(sprintf('%s: data is not an instance of %s', __CLASS__ . '\\' . __FUNCTION__, Dom::class));
        }

        $id = str_replace(uniqid('av', true), '.', '-');

        $links = $data->find('.seller-info-name a');

        if (!array_key_exists(0, $links)) {

            return $id;
        }

        $link = $links[0];

        preg_match('/\/user\/(.*)\/profile/', $link->href, $match);

        if (array_key_exists(1, $match)) {
            $id = 'av' . $match[1];
        }

        return $id;
    }
}

