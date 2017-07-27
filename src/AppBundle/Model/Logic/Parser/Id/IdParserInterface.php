<?php

namespace AppBundle\Model\Logic\Parser\Id;

interface IdParserInterface
{
    /**
     * @param array $data
     * @return string
     */
    public function parse(array $data);
}
