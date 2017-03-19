<?php

namespace AppBundle\Model\Filter;

use AppBundle\ODM\Document\Note;
use AppBundle\ODM\DocumentMapper\DataMapperFactory;

class PostActiveFilter
{
    private $dm_note;

    /**
     * PreUniqueFilter constructor.
     * @param DataMapperFactory $dm_factory
     */
    public function __construct(DataMapperFactory $dm_factory)
    {
        $this->dm_note = $dm_factory->init(Note::class);
    }

    /**
     * @param Note $note
     * @return bool
     */
    public function activate(Note $note)
    {
        $note->setActive(true);
        $this->dm_note->update($note);

        return true;
    }
}