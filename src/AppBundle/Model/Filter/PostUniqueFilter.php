<?php

namespace AppBundle\Model\Filter;

use AppBundle\ODM\Document\Note;
use AppBundle\ODM\DocumentMapper\DataMapperFactory;

class PostUniqueFilter
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
    public function filter(Note $note)
    {
        $duplicates = $this->dm_note->find([
            'description_hash' => $note->getDescriptionHash(),
            '_id'              => [
                '$ne' => $note->getId()
            ]
        ]);

        foreach ($duplicates as $duplicate) {
            $this->dm_note->delete($duplicate);
        }

        return true;
    }
}