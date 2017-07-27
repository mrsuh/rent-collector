<?php

namespace AppBundle\Model\Logic\Explorer\Subway;

use AppBundle\Model\Document\City\SubwayModel;
use Schema\City\Subway;

class SubwayExplorer
{
    private $subways;

    /**
     * SubwayExplorer constructor.
     * @param SubwayModel $model_subway
     */
    public function __construct(SubwayModel $model_subway)
    {
        $this->subways = $model_subway->findAll();
    }

    /**
     * @param string $text
     * @return Subway[]
     */
    public function explore(string $text)
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

