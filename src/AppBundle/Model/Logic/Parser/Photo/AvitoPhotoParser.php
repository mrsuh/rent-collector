<?php

namespace AppBundle\Model\Logic\Parser\Photo;

use AppBundle\Exception\ParseException;
use PHPHtmlParser\Dom;
use Schema\Note\Photo;

class AvitoPhotoParser implements PhotoParserInterface
{
    /**
     * @param $data
     * @return Photo[]
     * @throws ParseException
     */
    public function parse($data)
    {
        if (!($data instanceof Dom)) {
            throw new ParseException(sprintf('%s: data is not an instance of %s', __CLASS__ . '\\' . __FUNCTION__, Dom::class));
        }

        $elems = $data->find('.gallery-list-item-link');

        $photos = [];
        foreach ($elems as $elem) {

            preg_match('/\((.*)\)/', $elem->style, $match);

            if (!array_key_exists(1, $match)) {

                continue;
            }

            $link = $match[1];

            $low  = str_replace('75x55', '208x156', $link);
            $high = str_replace('75x55', '640x480', $link);

            $photos[] =
                (new Photo())
                    ->setLow('https:' . $low)
                    ->setHigh('https:' . $high);
        }

        return $photos;
    }
}
