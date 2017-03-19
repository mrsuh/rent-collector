<?php

namespace AppBundle\Model\Parser\Type;

use AppBundle\ODM\Document\Note;
use AppBundle\Service\TomitaService;

class TypeParserFactory
{
    private $tomita;

    /**
     * TypeParserFactory constructor.
     * @param $bin
     * @param $config
     */
    public function __construct($bin, $config)
    {
        $this->tomita = new TomitaService($bin, $config);
    }

    /**
     * @param $type
     * @return VkCommentTypeParser|VkWallTypeParser|null
     */
    public function init($type)
    {
        switch ($type) {
            case Note::VK_COMMENT:
                return new VkCommentTypeParser($this->tomita);
                break;
            case Note::VK_WALL:
                return new VkWallTypeParser($this->tomita);
                break;
        }

        return null;
    }
}

