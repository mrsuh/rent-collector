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

        return str_replace(uniqid('av', true), '.', '-');
    }
}

