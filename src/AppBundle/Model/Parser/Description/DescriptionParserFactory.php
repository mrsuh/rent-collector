<?php

namespace AppBundle\Model\Parser\Description;

use AppBundle\ODM\Document\Note;

class DescriptionParserFactory
{
    public function init($type)
    {
        switch ($type) {
            case Note::VK_COMMENT:
                return new VkCommentDescriptionParser();
                break;
            case Note::VK_WALL:
                return new VkWallDescriptionParser();
                break;
        }

        return null;
    }
}

