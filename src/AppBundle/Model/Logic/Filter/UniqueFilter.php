<?php

namespace AppBundle\Model\Logic\Filter;

use Schema\Note\Note;
use ODM\DocumentManager\DocumentManagerFactory;

class UniqueFilter
{
    /**
     * @var \ODM\DocumentManager\DocumentManager
     */
    private $dm_note;

    /**
     * UniqueFilter constructor.
     * @param DocumentManagerFactory $dm_factory
     */
    public function __construct(DocumentManagerFactory $dm_factory)
    {
        $this->dm_note = $dm_factory->init(Note::class);
    }

    /**
     * @param Note $note
     * @return Note[]
     */
    public function findDuplicates(Note $note): array
    {
        $date = \DateTime::createFromFormat('U', $note->getTimestamp());
        if (false === $date) {
            $date = new \DateTime();
        }

        return $this->dm_note->find(
            [
                'contact.person.id' => $note->getContact()->getExternalId(),
                'type'              => $note->getType(),
                'timestamp'         => [
                    '$gte' => $date->modify('- 12 hours')->getTimestamp(),
                ],
                'id'                => ['$ne' => $note->getId()]
            ]);
    }
}