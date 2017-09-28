<?php

namespace AppBundle\Model\Logic\Parser\Id;

interface IdParserInterface
{
    /**
     * @param $data
     * @return string
     */
    public function parse($data): string;
}
