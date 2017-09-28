<?php

namespace AppBundle\Model\Logic\Parser\Phone;

interface PhoneParserInterface
{
    /**
     * @param $data
     * @return int[]
     */
    public function parse($data);
}

