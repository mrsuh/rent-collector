<?php

namespace AppBundle\Model\Logic\Parser\ContactId;

use AppBundle\Exception\ParseException;

class VkCommentContactIdParser implements ContactIdParserInterface
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

        if (!array_key_exists('from_id', $data)) {
            throw new ParseException('Key "from_id" is not exists in array');
        }

        return (string)$data['from_id'];
    }
}

