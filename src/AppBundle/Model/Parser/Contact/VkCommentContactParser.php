<?php

namespace AppBundle\Model\Parser\Contact;

use AppBundle\Exception\ParseException;

class VkCommentContactParser implements ContactParserInterface
{
    /**
     * @param array $data
     * @return Contact
     * @throws ParseException
     */
    public function parse(array $data): Contact
    {
        if (!array_key_exists('from_id', $data)) {
            throw new ParseException('Key "from_id" is not exists in array');
        }

        $id = $data['from_id'];

        return (new Contact())
            ->setId($id)
            ->setLink('https://vk.com/id' . $id)
            ->setWrite('https://vk.com/write' . $id);
    }
}

