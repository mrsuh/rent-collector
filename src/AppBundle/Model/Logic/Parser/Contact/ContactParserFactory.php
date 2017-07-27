<?php

namespace AppBundle\Model\Logic\Parser\Contact;

use AppBundle\Exception\AppException;
use Schema\ParseList\Source;

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
            case Source::TYPE_VK_COMMENT:
                return new VkCommentContactParser();

                break;
            case Source::TYPE_VK_WALL:
                return new VkWallContactParser();

                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

