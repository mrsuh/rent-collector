<?php

namespace AppBundle\Model\Logic\Parser\Type;

use AppBundle\Exception\ParseException;
use PHPHtmlParser\Dom;
use Schema\Note\Note;

class AvitoTypeParser implements TypeParserInterface
{
    /**
     * @param $data
     * @return int
     * @throws ParseException
     */
    public function parse($data): int
    {
        if (!($data instanceof Dom)) {
            throw new ParseException(sprintf('%s: Data is not an instance of %s', __CLASS__ . '/' . __FUNCTION__, Dom::class));
        }


        $elems = $data->find('.title-info-title-text');
        $elem  = $elems[0];

        $type_str = mb_strtolower($elem->text);

        $type = Note::TYPE_ERR;
        switch (true) {
            case false !== mb_strrpos($type_str, '1-к'):
                $type = Note::TYPE_FLAT_1;
                break;
            case false !== mb_strrpos($type_str, '2-к'):
                $type = Note::TYPE_FLAT_2;
                break;
            case false !== mb_strrpos($type_str, '3-к'):
                $type = Note::TYPE_FLAT_3;
                break;
            case false !== mb_strrpos($type_str, '4-к'):
                $type = Note::TYPE_FLAT_N;
                break;
            case false !== mb_strrpos($type_str, 'комната'):
                $type = Note::TYPE_ROOM;
                break;
            case false !== mb_strrpos($type_str, 'студия'):
                $type = Note::TYPE_STUDIO;
                break;
        }

        return $type;
    }
}

