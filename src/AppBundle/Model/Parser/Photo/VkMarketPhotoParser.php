<?php

namespace AppBundle\Model\Parser\Photo;

use AppBundle\Exception\ParseException;

class VkMarketPhotoParser implements PhotoParserInterface
{
    /**
     * @param array $data
     * @return array
     * @throws ParseException
     */
    public function parse(array $data): array
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
