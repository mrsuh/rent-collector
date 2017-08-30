<?php

namespace AppBundle\Model\Logic\Filter\Unique;

use Schema\Note\Note;
use ODM\DocumentManager\DocumentManagerFactory;

class NoteFilter
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
                'contact.id' => $note->getContact()->getExternalId(),
                'type'       => $note->getType(),
                'timestamp'  => [
                    '$gte' => $date->modify('- 4 weeks')->getTimestamp(),
                ],
                '_id'        => ['$ne' => $note->getId()]
            ]);
    }
}