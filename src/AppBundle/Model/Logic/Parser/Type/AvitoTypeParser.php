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

        $elems = $data->find('.single-item-header .text');
        $elem  = $elems[1];

        $type_str = mb_strtolower($elem->text);

        $type = Note::TYPE_ERR;
        switch (true) {
            case false !== mb_strrpos($type_str, '1-к') && false !== mb_strrpos($type_str, 'кварт'):
                $type = Note::TYPE_FLAT_1;
                break;
            case false !== mb_strrpos($type_str, '2-к') && false !== mb_strrpos($type_str, 'кварт'):
                $type = Note::TYPE_FLAT_2;
                break;
            case false !== mb_strrpos($type_str, '3-к') && false !== mb_strrpos($type_str, 'кварт'):
                $type = Note::TYPE_FLAT_3;
                break;
            case false !== mb_strrpos($type_str, '4-к') && false !== mb_strrpos($type_str, 'кварт'):
                $type = Note::TYPE_FLAT_N;
                break;
            case false !== mb_strrpos($type_str, 'комн'):
                $type = Note::TYPE_ROOM;
                break;
            case false !== mb_strrpos($type_str, 'студ'):
                $type = Note::TYPE_STUDIO;
                break;
        }

        return $type;
    }
}

