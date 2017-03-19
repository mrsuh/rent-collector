<?php

namespace AppBundle\Model\Filter;

use AppBundle\ODM\Document\Note;

class PreDateFilter
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
    public function check(Note $note)
    {
        return (int)$this->timestamp <= (int)$note->getTimestamp();
    }
}