<?php

namespace AppBundle\Model\Logic\Parser\Description;

use AppBundle\Exception\ParseException;

class VkCommentDescriptionParser implements DescriptionParserInterface
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

        if (!array_key_exists('text', $data)) {
            throw new ParseException('Key "text" is not exists in array');
        }

        return $data['text'];
    }
}
