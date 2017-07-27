<?php

namespace AppBundle\Model\Logic\Parser\DateTime;

use AppBundle\Exception\AppException;
use Schema\ParseList\Source;

class DateTimeParserFactory
{
    /**
     * @param string $type
     * @return DateTimeParserInterface
     * @throws AppException
     */
    public function init(string $type): DateTimeParserInterface
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentDateTimeParser();
                break;
            case Source::TYPE_VK_WALL:
                return new VkWallDateTimeParser();
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

