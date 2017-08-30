<?php

namespace AppBundle\Model\Logic\Filter\Unique;

use ODM\DocumentManager\DocumentManagerFactory;
use Schema\Note\Note;

class DescriptionFilter
{
    /**
     * @var \ODM\DocumentManager\DocumentManager
     */
    private $dm_note;

    /**
     * DescriptionUniqueFilter constructor.
     * @param DocumentManagerFactory $dm_factory
     */
    public function __construct(DocumentManagerFactory $dm_factory)
    {
        $this->dm_note = $dm_factory->init(Note::class);
    }

    /**
     * @param Note $note
     * @return Note[]|array
     */
    public function findDuplicates(Note $note): array
    {
        return $this->dm_note->find(
            [
                'description_hash' => $note->getDescriptionHash(),
                '_id'              => ['$ne' => $note->getId()]
            ]);
    }
}