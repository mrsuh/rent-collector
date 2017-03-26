<?php

namespace AppBundle\Model\Filter;

use AppBundle\ODM\Document\Note;
use Jenssegers\ImageHash\ImageHash;
use ODM\DocumentMapper\DataMapperFactory;

class PhotoUniqueFilter
{
    private $dm_note;
    private $hasher;

    /**
     * PreUniqueFilter constructor.
     * @param DataMapperFactory $dm_factory
     */
    public function __construct(DataMapperFactory $dm_factory)
    {
        $this->dm_note = $dm_factory->init(Note::class);
        $this->hasher  = new ImageHash();
    }

    /**
     * @param Note $note
     * @return Note[]|array
     */
    public function check(Note $note)
    {
        $origin_hashes = $note->getPhotoHashes();

        if (empty($origin_hashes)) {
            return [];
        }

        $origin_hash = $origin_hashes[0];

        $duplicates = [];
        foreach ($this->dm_note->find() as $duplicate) {
            foreach ($duplicate->getPhotoHashes() as $hash) {
                if ($this->hasher->distance($origin_hash, $hash) <= 3) {
                    $duplicates[] = $duplicate;
                    break 2;
                }
            }
        }

        return $duplicates;
    }
}