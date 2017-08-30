<?php

namespace AppBundle\Model\Logic\Filter\Cleaner;

class DescriptionFilter
{
    public function clear(string $description): string
    {
        return preg_replace('/\-+/', '', $description);
    }
}