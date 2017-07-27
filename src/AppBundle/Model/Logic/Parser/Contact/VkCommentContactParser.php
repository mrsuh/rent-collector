<?php

namespace AppBundle\Model\Logic\Parser\Contact;

use AppBundle\Exception\ParseException;
use Schema\Note\Contact;

class VkCommentContactParser implements ContactParserInterface
{
    /**
     * @param array $data
     * @return Contact
     * @throws ParseException
     */
    public function parse(array $data)
    {
        if (!array_key_exists('from_id', $data)) {
            throw new ParseException('Key "from_id" is not exists in array');
        }

        $id = $data['from_id'];

        return (new Contact())
            ->setExternalId($id)
            ->setLink('https://vk.com/id' . $id);
    }
}

