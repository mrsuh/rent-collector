<?php

namespace AppBundle\Model\Parser\Price;

use AppBundle\Exception\ParseException;

class VkCommentPriceParser extends TextPriceParser
{
    public function parse(array $data)
    {
        if (!array_key_exists('text', $data)) {
            throw new ParseException('Key "text" is not exists in array');
        }

        return parent::parseText($data['text']);
    }
}

