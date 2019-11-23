<?php

namespace App\Filter;

use ODM\DocumentManager\DocumentManagerFactory;
use Schema\Note\Note;

class DuplicateFilter
{
    /**
     * @var \ODM\DocumentManager\DocumentManager
     */
    private $dm_note;

    public function __construct(DocumentManagerFactory $dm_factory)
    {
        $this->dm_note = $dm_factory->init(Note::class);
    }

    /**
     * @return Note[]
     */
    public function findDescriptionDuplicates(string $noteId, string $descriptionHash): array
    {
        return $this->dm_note->find(
            [
                'description_hash' => $descriptionHash,
                '_id'              => ['$ne' => $noteId]
            ]);
    }

    /**
     * @return Note[]
     */
    public function findIdDuplicates(string $noteId): array
    {
        return $this->dm_note->find(['_id' => $noteId]);
    }

    /**
     * @return Note[]
     */
    public function findContactAndTypeDuplicates(string $noteId, int $noteType, string $contactId): array
    {
        return $this->dm_note->find(
            [
                'contact.id' => $contactId,
                'type'       => $noteType,
                '_id'        => ['$ne' => $noteId]
            ]);
    }
}