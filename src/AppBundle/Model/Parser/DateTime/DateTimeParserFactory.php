<?php

namespace AppBundle\Model\Parser\DateTime;

use AppBundle\Document\Note;
use AppBundle\Exception\AppException;

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
            case Note::VK_COMMENT:
                return new VkCommentDateTimeParser();
                break;
            case Note::VK_WALL:
                return new VkWallDateTimeParser();
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

