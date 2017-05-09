<?php

namespace AppBundle\Model\Parser\Contact;

use AppBundle\Document\Note;
use AppBundle\Exception\AppException;

class ContactParserFactory
{
    /**
     * @param string $type
     * @return ContactParserInterface
     * @throws AppException
     */
    public function init(string $type): ContactParserInterface
    {
        switch ($type) {
            case Note::VK_COMMENT:
                return new VkCommentContactParser();

                break;
            case Note::VK_WALL:
                return new VkWallContactParser();

                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

