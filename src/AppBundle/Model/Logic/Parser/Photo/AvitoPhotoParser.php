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

        $elems = $data->find('[property="og:image"]');

        $photos = [];
        foreach ($elems as $elem) {

            $link = $elem->content;

            $low  = $link;
            $high = str_replace('640x480', '1280x960', $link);

            $photos[] =
                (new Photo())
                    ->setLow($low)
                    ->setHigh($high);
        }

        return $photos;
    }
}
