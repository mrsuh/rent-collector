<?php

namespace AppBundle\Model\Parser\Photo;

class VkWallPhotoParser
{
    public function parse(array $data)
    {
        $photos = [];

        if (!array_key_exists('attachments', $data)) {
            return $photos;
        }

        foreach ($data['attachments'] as $attachment) {
            if (!array_key_exists('photo', $attachment)) {
                continue;
            }

            $photo = $attachment['photo'];

            switch (true) {
                case array_key_exists('photo_604', $photo):
                    $low = $photo['photo_604'];
                    break;
                case array_key_exists('photo_130', $photo):
                    $low = $photo['photo_130'];
                    break;
                default:
                    $low = null;
            }

            switch (true) {
                case array_key_exists('photo_1280', $photo):
                    $high = $photo['photo_1280'];
                    break;
                case array_key_exists('photo_807', $photo):
                    $high = $photo['photo_807'];
                    break;
                case array_key_exists('photo_604', $photo):
                    $high = $photo['photo_604'];
                    break;
                default:
                    $high = null;
            }

            if (null === $low || null == $high) {
                continue;
            }

            $photos[] = [
                'low'  => $low,
                'high' => $high
            ];
        }

        return $photos;
    }
}
