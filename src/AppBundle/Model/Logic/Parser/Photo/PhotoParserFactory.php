<?php

namespace AppBundle\Model\Logic\Parser\Photo;

use AppBundle\Exception\AppException;
use Schema\ParseList\Source;

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
            case Source::TYPE_VK_COMMENT:
                return new VkCommentPhotoParser();
                break;
            case Source::TYPE_VK_WALL:
                return new VkWallPhotoParser();
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

