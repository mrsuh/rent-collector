<?php

namespace AppBundle\Model\Filter;

use AppBundle\ODM\Document\Note;
use Jenssegers\ImageHash\ImageHash;
use ODM\DocumentMapper\DataMapperFactory;
use Symfony\Component\Yaml\Yaml;

class PhotoBlackListFilter
{
    private $dm_note;
    private $hasher;
    private $black_list;

    /**
     * PreUniqueFilter constructor.
     * @param DataMapperFactory $dm_factory
     */
    public function __construct(DataMapperFactory $dm_factory, $file_black_list)
    {
        $this->dm_note    = $dm_factory->init(Note::class);
        $this->hasher     = new ImageHash();
        $this->black_list = Yaml::parse(file_get_contents($file_black_list));
    }

    /**
     * @param Note $note
     * @return bool
     */
    public function isAllow(Note $note)
    {
        $hashes = $note->getPhotoHashes();

        if (empty($hashes)) {
            return true;
        }

        foreach ($this->black_list as $black_hash) {
            foreach ($hashes as $hash) {
                if ($this->hasher->distance($hash, $black_hash) <= 3) {
                    return false;
                }
            }
        }

    }
}