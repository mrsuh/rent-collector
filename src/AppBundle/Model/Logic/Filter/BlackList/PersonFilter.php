<?php

namespace AppBundle\Model\Logic\Filter\BlackList;

use AppBundle\Model\Document\BlackList\BlackListModel;
use Schema\BlackList\Record;

class PersonFilter
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
        $this->black_list = $model_black_list->findByType(Record::TYPE_PERSON);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function isAllow(string $id)
    {
        foreach ($this->black_list as $record) {
            if (1 === preg_match('/' . $record->getRegexp() . '/', $id)) {

                return false;
            }
        }

        return true;
    }
}