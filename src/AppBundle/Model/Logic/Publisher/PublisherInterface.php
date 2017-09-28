<?php

namespace AppBundle\Model\Logic\Publisher;

use Schema\Note\Note;

interface PublisherInterface
{
    /**
     * @param Note $note
     * @return mixed
     */
    public function publish(Note $note);
}

