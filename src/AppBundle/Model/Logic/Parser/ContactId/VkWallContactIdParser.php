<?php

namespace AppBundle\Model\Logic\Parser\ContactId;

use AppBundle\Exception\ParseException;

class VkWallContactIdParser implements ContactIdParserInterface
{
    /**
     * @param $data
     * @return string
     * @throws ParseException
     */
    public function parse($data): string
    {
        if (!is_array($data)) {
            throw new ParseException(sprintf('%s: data is not an array', __CLASS__ . '\\' . __FUNCTION__));
        }

        switch (true) {
            case array_key_exists('signer_id', $data):
                $id = $data['signer_id'];

                break;
            case array_key_exists('from_id', $data):
                $id = $data['from_id'];

                break;
            case array_key_exists('owner_id', $data):
                $id = $data['owner_id'];

                break;
            default:
                $id = -1;

                break;
        }

        preg_match('/\[id(\d+)\|.*\]/', $data['text'], $match);
        $id_from_text = array_key_exists(1, $match) ? $match[1] : '';

        $id = !empty($id_from_text) ? $id_from_text : $id;

        return (string)$id;
    }
}

