<?php

namespace AppBundle\Model\Logic\Parser\Description;

interface DescriptionParserInterface
{
    /**
     * @param $data
     * @return string
     */
    public function parse($data): string;
}
