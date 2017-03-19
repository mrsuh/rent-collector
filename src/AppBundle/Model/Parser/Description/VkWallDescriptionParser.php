<?php

namespace AppBundle\Model\Parser\Description;

use AppBundle\Exception\ParseException;

class VkWallDescriptionParser
{
    public function parse(array $data)
    {
        if (!array_key_exists('date', $data)) {
            throw new ParseException('Key "text" is not exists in array');
        }

        return $data['text'];
    }
}
