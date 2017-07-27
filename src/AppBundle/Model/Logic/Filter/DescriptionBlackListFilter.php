<?php

namespace AppBundle\Model\Logic\Filter;

use Schema\Note\Note;
use AppBundle\Model\Document\BlackList\BlackListModel;
use Schema\BlackList\Record;

class DescriptionBlackListFilter
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
     * @param Note $note
     * @return bool
     */
    public function isAllow(Note $note): bool
    {
        $description = mb_strtolower($note->getDescription());

        foreach ($this->black_list as $record) {
            if (1 === preg_match('/' . preg_quote($record->getText()) . '/', $description)) {

                return false;
            }
        }

        return true;
    }
}