<?php

namespace AppBundle\Model\Logic\Parser\Id;

use AppBundle\Exception\AppException;
use Schema\ParseList\Source;

class IdParserFactory
{
    /**
     * @param string $type
     * @return IdParserInterface
     * @throws AppException
     */
    public function init(string $type): IdParserInterface
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentIdParser();

                break;
            case Source::TYPE_VK_WALL:
                return new VkWallIdParser();

                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

