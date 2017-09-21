<?php

namespace AppBundle\Model\Document\Note;

use Schema\City\City;
use Schema\Note\Note;
use ODM\DocumentManager\DocumentManagerFactory;
use ODM\Paginator\Paginator;

class NoteModel
{
    private $dm_note;

    /**
     * NoteModel constructor.
     * @param DocumentManagerFactory $dm
     */
    public function __construct(DocumentManagerFactory $dm)
    {
        $this->dm_note = $dm->init(Note::class);
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
     * @return Note[]
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
     * @param \DateTime $from
     * @param \DateTime $to
     * @return Note[]
     */
    public function findPublishedNotesByCityForPeriod(City $city, \DateTime $from, \DateTime $to)
    {
        return $this->dm_note->find([
            'published_timestamp' => [
                '$gte' => $from->getTimestamp(),
                '$lte' => $to->getTimestamp()
            ],
            'city'                => $city->getShortName(),
            'published'           => 1
        ]);
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

    /**
     * @param Note $note
     * @return bool
     */
    public function update(Note $note)
    {
        $this->dm_note->update($note);

        return true;
    }

    /**
     * @param Note $note
     * @return bool
     */
    public function delete(Note $note)
    {
        $this->dm_note->delete($note);

        return true;
    }
}
