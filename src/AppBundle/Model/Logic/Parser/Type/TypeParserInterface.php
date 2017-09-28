<?php

namespace AppBundle\Model\Logic\Parser\Type;

interface TypeParserInterface
{
    /**
     * @param $data
     * @return int
     */
    public function parse($data): int;
}

