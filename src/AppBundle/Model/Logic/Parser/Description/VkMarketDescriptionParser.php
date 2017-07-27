<?php

namespace AppBundle\Model\Logic\Parser\Description;

use AppBundle\Exception\ParseException;

class VkMarketDescriptionParser implements DescriptionParserInterface
{
    /**
     * @param array $data
     * @return string
     * @throws ParseException
     */
    public function parse(array $data): string
    {
        if (!array_key_exists('date', $data)) {
            throw new ParseException('Key "text" is not exists in array');
        }

        return $data['text'];
    }
}
