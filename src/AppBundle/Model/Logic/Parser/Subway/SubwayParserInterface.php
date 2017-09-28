<?php

namespace AppBundle\Model\Logic\Parser\Subway;

use Schema\City\Subway;

interface SubwayParserInterface
{
    /**
     * @param        $data
     * @param string $city
     * @return Subway[]
     */
    public function parse($data, string $city);
}
