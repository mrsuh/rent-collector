<?php

namespace AppBundle\Model\Logic\Parser\Id;

use AppBundle\Exception\ParseException;

class VkCommentIdParser implements IdParserInterface
{
    /**
     * @param array $data
     * @return string
     * @throws ParseException
     */
    public function parse(array $data)
    {
        if (!array_key_exists('id', $data)) {
            throw new ParseException('Key "id" is not exists in array');
        }

        return $data['id'];
    }
}

