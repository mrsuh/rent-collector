<?php

namespace AppBundle\Model\Logic\Parser\ContactId;

interface ContactIdParserInterface
{
    /**
     * @param $data
     * @return string
     */
    public function parse($data): string;
}
