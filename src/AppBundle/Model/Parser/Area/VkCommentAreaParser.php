<?php

namespace AppBundle\Model\Parser\Area;

use AppBundle\Exception\ParseException;

class VkCommentAreaParser extends TextAreaParser
{
    public function parse(array $data)
    {
        if (!array_key_exists('text', $data)) {
            throw new ParseException('Key "text" is not exists in array');
        }

        return parent::parseText($data['text']);
    }
}

