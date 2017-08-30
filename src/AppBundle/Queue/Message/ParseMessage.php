<?php

namespace AppBundle\Queue\Message;

use Schema\Note\Note;
use Schema\Parse\Record\Source;

class ParseMessage
{
    private $id;

    private $note;

    /**
     * @var Source
     */
    private $source;

    public function __construct()
    {
        $this->id = uniqid();
    }

    /**
     * @return Source|null
     */
    public function getSource()
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
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param $note
     * @return $this
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }
}