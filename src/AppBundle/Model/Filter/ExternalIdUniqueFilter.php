<?php

namespace AppBundle\Model\Filter;

use AppBundle\Document\Note;
use ODM\DocumentMapper\DataMapperFactory;

class ExternalIdUniqueFilter
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
     * @return Note[]|array
     */
    public function findDuplicates(Note $note): array
    {
        return $this->dm_note->find([
            'external_id' => $note->getExternalId(),
            'source'      => $note->getSource(),
            'id'          => ['$ne' => $note->getId()]
        ]);
    }
}