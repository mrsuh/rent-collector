<?php

namespace App\Queue\Message;

use Schema\Note\Note;

class NotifyMessage
{
    /**
     * @var Note
     */
    private $note;

    /**
     * @return Note
     */
    public function getNote(): Note
    {
        return $this->note;
    }

    /**
     * @param Note $note
     * @return $this
     */
    public function setNote(Note $note)
    {
        $this->note = $note;

        return $this;
    }
}