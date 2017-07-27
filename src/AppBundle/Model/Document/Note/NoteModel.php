<?php

namespace AppBundle\Model\Document\Note;

use Schema\Note\Note;
use ODM\DocumentManager\DocumentManagerFactory;
use ODM\Paginator\Paginator;

class NoteModel
{
    private $dm_note;
    private $dm_note_cold;

    /**
     * NoteModel constructor.
     * @param DocumentManagerFactory $dm
     */
    public function __construct(DocumentManagerFactory $dm, DocumentManagerFactory $dm_cold)
    {
        $this->dm_note      = $dm->init(Note::class);
        $this->dm_note_cold = $dm->init(Note::class);
    }

    /**
     * @param int $current_page
     * @return Paginator
     */
    public function paginateAll(int $current_page = 1)
    {
        $query = $this->dm_note->createQuery();

        return Paginator::paginate($query, $current_page);
    }

    /**
     * @return array|Note[]
     */
    public function findAll()
    {
        return $this->dm_note->find();
    }

    /**
     * @return null|Note
     */
    public function findOneById($id)
    {
        return $this->dm_note->findOne(['_id' => $id]);
    }

    /**
     * @param Note $note
     * @return bool
     */
    public function replaceToColdDB(Note $note)
    {
        $this->dm_note_cold->insert($note);
        $this->dm_note->delete($note);

        return true;
    }


    /**
     * @param Note $note
     * @return bool
     */
    public function create(Note $note)
    {
        $this->dm_note->insert($note);

        return true;
    }
}
