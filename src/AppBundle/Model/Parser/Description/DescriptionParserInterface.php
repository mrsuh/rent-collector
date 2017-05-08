<?php

namespace AppBundle\Model\Parser\Description;

interface DescriptionParserInterface
{
    /**
     * @param array $data
     * @return string
     */
    public function parse(array $data): string;
}
