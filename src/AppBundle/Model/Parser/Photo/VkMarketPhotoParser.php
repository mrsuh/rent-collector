<?php

namespace AppBundle\Model\Parser\Photo;

use AppBundle\Exception\ParseException;

class VkMarketPhotoParser
{
    public function parse(array $data)
    {
        $photos = [];

        if (!array_key_exists('date', $data)) {
            return $photos;
        }

        foreach ($data['attachments'] as $attachment) {
            if (!array_key_exists('photo', $attachment)) {
                continue;
            }

            $photo = $attachment['photo'];

            if (!array_key_exists('photo_130', $photo) || !array_key_exists('photo_604', $photo)) {
                throw new ParseException('Invalid parse photo ', json_encode($photo));
            }

            $photos[] = [
                'low'  => $photo['photo_130'],
                'high' => $photo['photo_604']
            ];
        }


        return $photos;
    }
}
