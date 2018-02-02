<?php

namespace AppBundle\Model\Logic\Parser\Description;

use AppBundle\Exception\ParseException;
use PHPHtmlParser\Dom;

class AvitoDescriptionParser implements DescriptionParserInterface
{
    /**
     * @param $data
     * @return string
     * @throws ParseException
     */
    public function parse($data): string
    {
        if (!($data instanceof Dom)) {
            throw new ParseException(sprintf('%s: Data is not an instance of %s', __CLASS__ . '/' . __FUNCTION__, Dom::class));
        }

        $elems = $data->find('.description-preview-wrapper p');

        $description = '';
        foreach ($elems as $elem) {
            $description .= $elem->text . PHP_EOL;
        }

        return $description;
    }
}
