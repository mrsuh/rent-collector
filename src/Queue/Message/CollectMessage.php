<?php

namespace App\Queue\Message;

use Schema\Note\Note;
use Schema\Parse\Record\Source;

class CollectMessage
{
    /**
     * @var Source
     */
    private $source;

    /**
     * @var Note
     */
    private $note;

    /**
     * @return Source
     */
    public function getSource(): Source
    {
        return $this->source;
    }

    /**
     * @param Source $source
     * @return $this
     */
    public function setSource(Source $source)
    {
        $this->source = $source;

        return $this;
    }

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