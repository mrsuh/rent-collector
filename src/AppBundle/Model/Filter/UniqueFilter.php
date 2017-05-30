<?php

namespace AppBundle\Model\Filter;

use AppBundle\Document\Note;
use ODM\DocumentMapper\DataMapperFactory;

class UniqueFilter
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
        $contact = $note->getContacts()['person']['link'];

        $date = \DateTime::createFromFormat('U', $note->getTimestamp());
        if (false === $date) {
            $date = new \DateTime();
        }

        return $this->dm_note->find(
            [
                'contacts'  => [
                    'person' => [
                        'link' => $contact
                    ]
                ],
                'type'      => $note->getType(),
                'timestamp' => [
                    '$gte' => $date->modify('- 12 hours')->getTimestamp(),
                ],
                'id'        => ['$ne' => $note->getId()]
            ]);
    }
}