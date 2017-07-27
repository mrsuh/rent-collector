<?php

namespace AppBundle\Model\Logic\Parser\DateTime;

use AppBundle\Exception\ParseException;

class VkWallDateTimeParser implements DateTimeParserInterface
{
    /**
     * @param array $data
     * @return int
     * @throws ParseException
     */
    public function parse(array $data): int
    {
        if (!array_key_exists('date', $data)) {
            throw new ParseException('Key "date" is not exists in array');
        }

        return (int)$data['date'];
    }
}

