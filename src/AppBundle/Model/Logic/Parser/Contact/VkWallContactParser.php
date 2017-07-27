<?php

namespace AppBundle\Model\Logic\Parser\Contact;

use Schema\Note\Contact;

class VkWallContactParser implements ContactParserInterface
{
    /**
     * @param array $data
     * @return Contact
     */
    public function parse(array $data)
    {
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

        $text = $data['text'];

        if ($id < 0) {
            preg_match('/\[id(\d+)\|.*\]/', $text, $match);
            $id = array_key_exists(1, $match) ? $match[1] : -1;
        }

        return (new Contact())
            ->setExternalId($id)
            ->setLink('https://vk.com/' . ($id > 0 ? 'id' . $id : 'club' . $id));
    }
}

