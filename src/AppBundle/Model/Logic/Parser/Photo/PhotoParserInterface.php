<?php

namespace AppBundle\Model\Logic\Parser\Photo;

use Schema\Note\Photo;

interface PhotoParserInterface
{
    /**
     * @param $data
     * @return Photo[]
     */
    public function parse($data);
}
