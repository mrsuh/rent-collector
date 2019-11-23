<?php

namespace App\Document\Parse\Record;

use ODM\DocumentManager\DocumentManagerFactory;
use Schema\Parse\Record\Record;

class RecordModel
{
    private $dm;

    /**
     * ParseModel constructor.
     * @param DocumentManagerFactory $dm
     */
    public function __construct(DocumentManagerFactory $dm)
    {
        $this->dm = $dm->init(Record::class);
    }

    /**
     * @return array|Record[]
     */
    public function findAll()
    {
        return $this->dm->find();
    }

    /**
     * @param string $city
     * @return array|Record[]
     */
    public function findByCity(string $city)
    {
        return $this->dm->find(['city' => $city]);
    }

    /**
     * @param $id
     * @return null|Record
     */
    public function findOneById($id)
    {
        return $this->dm->findOne(['_id' => $id]);
    }

    /**
     * @param string $name
     * @return null|Record
     */
    public function findOneByName(string $name)
    {
        return $this->dm->findOne(['name' => $name]);
    }

    /**
     * @param Record $obj
     * @return Record
     */
    public function create(Record $obj)
    {
        $this->dm->insert($obj);

        return $obj;
    }

    /**
     * @param Record $obj
     * @return Record
     */
    public function update(Record $obj)
    {
        $this->dm->update($obj);

        return $obj;
    }

    /**
     * @param Record $obj
     * @return \MongoDB\DeleteResult
     */
    public function delete(Record $obj)
    {
        return $this->dm->delete($obj);
    }
}
