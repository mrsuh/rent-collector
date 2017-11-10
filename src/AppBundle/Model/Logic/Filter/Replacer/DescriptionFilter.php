<?php

namespace AppBundle\Model\Logic\Filter\Replacer;

class DescriptionFilter
{
    public function replace(string $description): string
    {
        return preg_replace('/8?\D{0,2}(\D{0,2}\d){10}/', '**********', $description);
    }
}