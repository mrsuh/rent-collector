<?php

namespace AppBundle\Model\Parser\Photo;

interface PhotoParserInterface
{
    /**
     * @param array $data
     * @return array
     */
    public function parse(array $data): array;
}
