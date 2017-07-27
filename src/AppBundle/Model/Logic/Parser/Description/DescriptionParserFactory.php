<?php

namespace AppBundle\Model\Logic\Parser\Description;

use AppBundle\Exception\AppException;
use Schema\ParseList\Source;

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
            case Source::TYPE_VK_COMMENT:
                return new VkCommentDescriptionParser();
                break;
            case Source::TYPE_VK_WALL:
                return new VkWallDescriptionParser();
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

