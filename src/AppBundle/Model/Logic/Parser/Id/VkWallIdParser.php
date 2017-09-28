<?php

namespace AppBundle\Model\Logic\Parser\Id;

use AppBundle\Exception\ParseException;

class VkWallIdParser implements IdParserInterface
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

        if (!array_key_exists('id', $data)) {
            throw new ParseException('Key "id" is not exists in array');
        }

        return (string)$data['id'];
    }
}

