<?php

namespace AppBundle\Model\Logic\Parser\Photo;

use Schema\Note\Photo;

interface PhotoParserInterface
{
    /**
     * @param array $data
     * @return Photo[]
     */
    public function parse(array $data);
}
