<?php

namespace AppBundle\Model\Parser\Contact;

use AppBundle\ODM\Document\Note;
use AppBundle\Service\TomitaService;

class ContactParserFactory
{
    private $tomita;

    /**
     * ContactParserFactory constructor.
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
                return new VkCommentContactParser($this->tomita);
                break;
            case Note::VK_WALL:
                return new VkWallContactParser($this->tomita);
                break;
        }

        return null;
    }
}

