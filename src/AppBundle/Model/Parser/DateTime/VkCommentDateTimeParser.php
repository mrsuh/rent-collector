<?php

namespace AppBundle\Model\Parser\DateTime;

use AppBundle\Exception\ParseException;

class VkCommentDateTimeParser
{
    public function parse(array $data)
    {
        if (!array_key_exists('date', $data)) {
            throw new ParseException('Key "date" is not exists in array');
        }

        return $data['date'];
    }
}

