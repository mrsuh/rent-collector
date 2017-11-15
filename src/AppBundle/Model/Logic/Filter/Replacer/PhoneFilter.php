<?php

namespace AppBundle\Model\Logic\Filter\Replacer;

class PhoneFilter
{
    public function replace(string $description): string
    {
        return preg_replace('/((7|8)\D{0,2})?(\d\D{0,2}){9}\d/', '[номер скрыт]', $description);
    }
}