<?php

namespace AppBundle\Model\Logic\Parser\Price;

interface PriceParserInterface
{
    /**
     * @param $data
     * @return int
     */
    public function parse($data);
}
