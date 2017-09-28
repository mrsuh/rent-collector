<?php

namespace AppBundle\Model\Logic\Parser\ContactName;

use AppBundle\Exception\ParseException;
use PHPHtmlParser\Dom;

class AvitoContactNameParser implements ContactNameParserInterface
{
    /**
     * @param $data
     * @return string
     * @throws ParseException
     */
    public function parse($data): string
    {
        if (!($data instanceof Dom)) {
            throw new ParseException(sprintf('%s: Data is not an instance of %s', __CLASS__ . '\\' . __FUNCTION__, Dom::class));
        }

        $elems = $data->find('.seller-info-name a ');

        if (array_key_exists(0, $elems)) {
            throw new ParseException('Array of elems has not key "0"');
        }

        $elem = $elems[0];

        return (string)$elem->text;
    }
}

