<?php

namespace AppBundle\Model\Logic\Filter\Replacer;

class VkIdFilter
{
    public function replace(string $description): string
    {
        return preg_replace('/\[id\d+\|\D+\]/', '[id скрыт]', $description);
    }
}