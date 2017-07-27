<?php

namespace AppBundle\Model\Logic\Parser\Description;

interface DescriptionParserInterface
{
    /**
     * @param array $data
     * @return string
     */
    public function parse(array $data): string;
}
