<?php

namespace AppBundle\Model\Parser\Price;

use AppBundle\ODM\Document\Note;
use AppBundle\Service\TomitaService;

class PriceParserFactory
{
    private $tomita;

    /**
     * PriceParserFactory constructor.
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
                return new VkCommentPriceParser($this->tomita);
                break;
            case Note::VK_WALL:
                return new VkWallPriceParser($this->tomita);
                break;
        }

        return null;
    }
}

