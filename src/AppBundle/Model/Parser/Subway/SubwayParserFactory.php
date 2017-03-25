<?php

namespace AppBundle\Model\Parser\Subway;

use AppBundle\ODM\Document\Note;
use ODM\DocumentMapper\DataMapperFactory;

class SubwayParserFactory
{
    private $odm;

    public function __construct(DataMapperFactory $odm)
    {
        $this->odm = $odm;
    }

    public function init($type)
    {
        switch ($type) {
            case Note::VK_COMMENT:
                return new VkCommentSubwayParser($this->odm);
                break;
            case Note::VK_WALL:
                return new VkWallSubwayParser($this->odm);
                break;
        }

        return null;
    }
}

