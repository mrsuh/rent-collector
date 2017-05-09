<?php

namespace AppBundle\Model\Parser\Photo;

use AppBundle\Document\Note;
use AppBundle\Exception\AppException;

class PhotoParserFactory
{
    /**
     * @param string $type
     * @return PhotoParserInterface
     * @throws AppException
     */
    public function init(string $type): PhotoParserInterface
    {
        switch ($type) {
            case Note::VK_COMMENT:
                return new VkCommentPhotoParser();
                break;
            case Note::VK_WALL:
                return new VkWallPhotoParser();
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

