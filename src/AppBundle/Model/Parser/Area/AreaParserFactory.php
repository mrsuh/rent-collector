<?php

namespace AppBundle\Model\Parser\Area;

use AppBundle\ODM\Document\Note;
use AppBundle\Service\TomitaService;

class AreaParserFactory
{
    private $tomita;

    /**
     * AreaParserFactory constructor.
     * @param $bin
     * @param $config
     */
    public function __construct($bin, $config)
    {
        $this->tomita = new TomitaService($bin, $config);
    }

    public function init($type)
    {
        switch ($type) {
            case Note::VK_COMMENT:
                return new VkCommentAreaParser($this->tomita);
                break;
            case Note::VK_WALL:
                return new VkWallAreaParser($this->tomita);
                break;

        }

        return null;
    }
}

