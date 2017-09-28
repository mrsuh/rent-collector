<?php

namespace AppBundle\Model\Logic\Parser\DateTime;

use AppBundle\Exception\ParseException;

class VkWallDateTimeParser implements DateTimeParserInterface
{
    /**
     * @param mixed $data
     * @return int
     * @throws ParseException
     */
    public function parse($data): int
    {
        if (!is_array($data)) {
            throw new ParseException(sprintf('%s: data is not an array', __CLASS__ . '\\' . __FUNCTION__));
        }

        if (!array_key_exists('date', $data)) {
            throw new ParseException('Key "date" is not exists in array');
        }

        return (int)$data['date'];
    }
}

