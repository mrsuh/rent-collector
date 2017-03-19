<?php

namespace AppBundle\Model\Parser\DateTime;

use AppBundle\Exception\ParseException;

class VkMarketDateTimeParser
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
            throw new ParseException('Key "date" is not exists in array');
        }

        return $data['date'];
    }
}

