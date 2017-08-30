<?php

namespace AppBundle\Model\Logic\Explorer\Subway;

use Schema\City\Subway;

class SubwayExplorer
{
    private $subways;

    /**
     * SubwayExplorer constructor.
     * @param Subway[] $subways
     */
    public function __construct(array $subways)
    {
        $this->subways = $subways;
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

