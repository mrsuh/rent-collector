<?php

namespace AppBundle\Model\Parser\DateTime;

use AppBundle\ODM\Document\Note;

class DateTimeParserFactory
{
    public function init($type)
    {
        switch ($type) {
            case Note::VK_COMMENT:
                return new VkCommentDateTimeParser();
                break;
            case Note::VK_WALL:
                return new VkWallDateTimeParser();
                break;
        }

        return null;
    }
}

