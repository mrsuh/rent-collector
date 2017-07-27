<?php

namespace AppBundle\Model\Document\PublishList;

use Schema\PublishList\Record;
use ODM\DocumentManager\DocumentManagerFactory;

class PublishListModel
{
    private $dm_publish;

    /**
     * PublishModel constructor.
     * @param DocumentManagerFactory $dm
     */
    public function __construct(DocumentManagerFactory $dm)
    {
        $this->dm_publish = $dm->init(Record::class);
    }

    /**
     * @return array|Record[]
     */
    public function findAll()
    {
        return $this->dm_publish->find();
    }

    /**
     * @param $id
     * @return null|Record
     */
    public function findOneById($id)
    {
        return $this->dm_publish->findOne(['_id' => $id]);
    }

    /**
     * @param Record $obj
     * @return Record
     */
    public function create(Record $obj)
    {
        $this->dm_publish->insert($obj);

        return $obj;
    }

    /**
     * @param Record $obj
     * @return Record
     */
    public function update(Record $obj)
    {
        $this->dm_publish->update($obj);

        return $obj;
    }
}
