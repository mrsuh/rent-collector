<?php

namespace AppBundle\Model\Parser\Description;

use AppBundle\Exception\ParseException;

class VkMarketDescriptionParser
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

        if (!array_key_exists('date', $data)) {
            throw new ParseException('Key "text" is not exists in array');
        }

        return $data['text'];
    }
}
