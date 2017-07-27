<?php

namespace AppBundle\Model\Logic\Parser\DateTime;

interface DateTimeParserInterface
{
    /**
     * @param array $data
     * @return int
     */
    public function parse(array $data): int;
}

