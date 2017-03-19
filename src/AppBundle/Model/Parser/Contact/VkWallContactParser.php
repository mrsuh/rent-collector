<?php

namespace AppBundle\Model\Parser\Contact;

use AppBundle\Exception\ParseException;

class VkWallContactParser extends TextContactParser
{
    public function parse(array $data)
    {
        if (!array_key_exists('text', $data)) {
            throw new ParseException('Key "text" is not exists in array');
        }

        switch (true) {
            case array_key_exists('signer_id', $data):
                $id = $data['signer_id'];
                break;
            case array_key_exists('from_id', $data):
                $id = $data['from_id'];
                break;
            default:
                $id = $data['owner_id'];

                break;
        }

        if ($id > 0) {
            $links = [
                'link'  => 'https://vk.com/id' . $id,
                'write' => 'https://vk.com/write' . $id
            ];

        } else {
            $links = [
                'link'  => 'https://vk.com/club' . abs($id),
                'write' => 'https://vk.com/club' . abs($id)
            ];
        }

        $data = [
            'phones' => parent::parseText($data['text']),
            'link'   => $links['link'],
            'write'  => $links['write']
        ];

        return $data;
    }
}

