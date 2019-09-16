<?php

namespace App\Filter;

class ReplaceFilter
{
    public function replace(string $description): string
    {
        $pattern     = [
            '/\-+/',
            '/((7|8)\D{0,2})?(\d\D{0,2}){9}\d/',
            '/\[id\d+\|\D+\]/'
        ];
        $replacement = [
            '',
            '[номер скрыт]',
            '[id скрыт]'
        ];

        return preg_replace($pattern, $replacement, $description);
    }
}