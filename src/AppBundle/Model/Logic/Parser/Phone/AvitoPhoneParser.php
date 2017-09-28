<?php

namespace AppBundle\Model\Logic\Parser\Phone;

use AppBundle\Exception\ParseException;
use PHPHtmlParser\Dom;

class AvitoPhoneParser implements PhoneParserInterface
{
    /**
     * @param $data
     * @return int[]
     * @throws ParseException
     */
    public function parse($data)
    {
        if (!($data instanceof Dom)) {
            throw new ParseException(sprintf('%s: Data is not an instance of %s', __CLASS__ . '/' . __FUNCTION__, Dom::class));
        }

        return [];
    }
}

