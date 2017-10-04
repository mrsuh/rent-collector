<?php

namespace AppBundle\Model\Logic\Filter\BlackList;

use AppBundle\Model\Document\BlackList\BlackListModel;
use Schema\BlackList\Record;

class DescriptionFilter
{
    /**
     * @var Record[]
     */
    private $black_list;

    /**
     * DescriptionBlackListFilter constructor.
     * @param BlackListModel $model_black_list
     */
    public function __construct(BlackListModel $model_black_list)
    {
        $this->black_list = $model_black_list->findByType(Record::TYPE_DESCRIPTION);
    }

    /**
     * @param string $description_raw
     * @return bool
     */
    public function isAllow(string $description_raw): bool
    {
        $description = mb_strtolower($description_raw);

        foreach ($this->black_list as $record) {
            echo $record->getRegexp() . PHP_EOL;
            if (1 === preg_match('/' . $record->getRegexp() . '/', $description)) {

                return false;
            }
        }

        return true;
    }
}