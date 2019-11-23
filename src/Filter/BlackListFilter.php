<?php

namespace App\Filter;

use App\Document\BlackList\BlackListModel;
use Schema\BlackList\Record;

class BlackListFilter
{
    /**
     * @var Record[]
     */
    private $black_list;

    public function __construct(BlackListModel $model_black_list)
    {
        $this->black_list = $model_black_list->findAll();
    }

    public function isAllow(string $text): bool
    {
        $description = mb_strtolower($text);

        foreach ($this->black_list as $record) {

            if (1 === preg_match('/' . $record->getRegexp() . '/', $description)) {

                return false;
            }
        }

        return true;
    }
}