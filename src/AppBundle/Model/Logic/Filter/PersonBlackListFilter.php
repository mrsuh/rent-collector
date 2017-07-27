<?php

namespace AppBundle\Model\Logic\Filter;

use Schema\Note\Note;
use AppBundle\Model\Document\BlackList\BlackListModel;
use Schema\BlackList\Record;

class PersonBlackListFilter
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
     * @param Note $note
     * @return bool
     */
    public function isAllow(Note $note)
    {
        $id = $note->getContact()->getExternalId();

        foreach ($this->black_list as $record) {
            if (1 === preg_match('/' . preg_quote($record->getText()) . '/', $id)) {

                return false;
            }
        }

        return true;
    }
}