<?php

namespace AppBundle\Model\Logic\Parser\DateTime;

interface DateTimeParserInterface
{
    /**
     * @param $data
     * @return int
     */
    public function parse($data): int;
}

