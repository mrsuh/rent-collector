<?php

namespace AppBundle\Model\Parser\Subway;

use AppBundle\ODM\Document\Subway;
use AppBundle\ODM\DocumentMapper\DataMapperFactory;

class TextSubwayParser
{
    private $subways;

    public function __construct(DataMapperFactory $odm)
    {
        $repo = $odm->init(Subway::class);

        $this->subways = $repo->find();
    }

    public function parseText($text)
    {
        $subways = [];
        foreach ($this->subways as $subway) {
            if (1 === preg_match($subway->getRegexp(), $text)) {
                $subways[] = $subway;
            }
        }

        return $subways;
    }
}

