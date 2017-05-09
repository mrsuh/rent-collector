<?php

namespace AppBundle\Model\Parser\Description;

use AppBundle\Document\Note;
use AppBundle\Exception\AppException;

class DescriptionParserFactory
{
    /**
     * @param string $type
     * @return DescriptionParserInterface
     * @throws AppException
     */
    public function init(string $type): DescriptionParserInterface
    {
        switch ($type) {
            case Note::VK_COMMENT:
                return new VkCommentDescriptionParser();
                break;
            case Note::VK_WALL:
                return new VkWallDescriptionParser();
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

