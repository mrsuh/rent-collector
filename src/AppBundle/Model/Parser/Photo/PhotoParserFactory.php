<?php

namespace AppBundle\Model\Parser\Photo;

use AppBundle\ODM\Document\Note;

class PhotoParserFactory
{
    public function init($type)
    {
        switch ($type) {
            case Note::VK_COMMENT:
                return new VkCommentPhotoParser();
                break;
            case Note::VK_WALL:
                return new VkWallPhotoParser();
                break;
        }

        return null;
    }
}

