<?php

namespace AppBundle\Model\Parser\Contact;

use AppBundle\Exception\ParseException;

class VkCommentContactParser extends TextContactParser
{
    public function parse(array $data)
    {
        if (!array_key_exists('text', $data)) {
            throw new ParseException('Key "text" is not exists in array');
        }

        if (!array_key_exists('from_id', $data)) {
            throw new ParseException('Key "from_id" is not exists in array');
        }

        $data = [
            'phones' => parent::parseText($data['text']),
            'link'   => 'https://vk.com/id' . $data['from_id'],
            'write'  => 'https://vk.com/write' . $data['from_id']
        ];

        return $data;
    }
}

