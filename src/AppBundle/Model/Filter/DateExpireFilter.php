<?php

namespace AppBundle\Model\Filter;

use AppBundle\ODM\Document\Note;

class DateExpireFilter
{
    private $timestamp;

    /**
     * DateFilter constructor.
     */
    public function __construct()
    {
        $this->timestamp = (new \DateTime())->modify('- 2 week')->getTimestamp();
    }

    /**
     * @param Note $note
     * @return bool
     */
    public function isExpire(Note $note)
    {
        return (int)$this->timestamp >= (int)$note->getTimestamp();
    }
}