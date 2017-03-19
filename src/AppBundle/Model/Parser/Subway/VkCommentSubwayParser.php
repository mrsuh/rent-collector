<?php

namespace AppBundle\Model\Parser\Subway;

use AppBundle\Exception\ParseException;

class VkCommentSubwayParser extends TextSubwayParser
{
    public function parse(array $data)
    {
        if (!array_key_exists('text', $data)) {
            throw new ParseException('Key "text" is not exists in array');
        }

        $text = mb_strtolower($data['text']);

        return parent::parseText($text);
    }
}

