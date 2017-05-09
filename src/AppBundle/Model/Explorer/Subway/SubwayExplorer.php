<?php

namespace AppBundle\Model\Explorer\Subway;

use AppBundle\Document\Subway;
use ODM\DocumentMapper\DataMapperFactory;

class SubwayExplorer
{
    private $subways;

    /**
     * SubwayExplorer constructor.
     * @param DataMapperFactory $odm
     */
    public function __construct(DataMapperFactory $odm)
    {
        $repo = $odm->init(Subway::class);

        $this->subways = $repo->find();
    }

    /**
     * @param string $text
     * @return Subway[]|array
     */
    public function explore(string $text): array
    {
        $text = mb_strtolower($text);

        $subways = [];
        foreach ($this->subways as $subway) {
            if (1 === preg_match($subway->getRegexp(), $text)) {
                $subways[] = $subway;
            }
        }

        return $subways;
    }
}

