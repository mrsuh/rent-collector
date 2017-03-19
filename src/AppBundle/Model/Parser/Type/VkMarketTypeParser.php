<?php

namespace AppBundle\Model\Parser\Type;

use AppBundle\Exception\ParseException;

class VkMarketTypeParser extends TextTypeParser
{
    public function parse($json)
    {
        $data = json_decode($json, true);

        if (false === $data) {
            throw new ParseException('Invalid Json');
        }

        if (!is_array($data)) {
            throw new ParseException('Data is not a array');
        }

        if (!array_key_exists('text', $data)) {
            throw new ParseException('Key "text" is not exists in array');
        }

        return parent::parseText($data['text']);
    }
}

