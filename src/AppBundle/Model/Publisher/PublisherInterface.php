<?php

namespace AppBundle\Model\Publisher;

use AppBundle\Document\Note;

interface PublisherInterface
{
    /**
     * @param Note $note
     * @return bool
     */
    public function publish(Note $note): bool;
}

