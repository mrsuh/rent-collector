<?php

namespace AppBundle\Model\Logic\Publisher;

use Schema\Note\Note;
use Schema\Publish\Record\Record;
use Schema\Publish\User\User;

interface PublisherInterface
{
    /**
     * @param Note $note
     * @return mixed
     */
    public function publish(Note $note);
}

