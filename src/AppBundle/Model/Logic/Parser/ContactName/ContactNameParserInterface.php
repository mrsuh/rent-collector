<?php

namespace AppBundle\Model\Logic\Parser\ContactName;

interface ContactNameParserInterface
{
    /**
     * @param $data
     * @return string
     */
    public function parse($data): string;
}
