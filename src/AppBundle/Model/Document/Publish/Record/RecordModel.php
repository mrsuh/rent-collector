<?php

namespace AppBundle\Model\Document\Publish\Record;

use ODM\DocumentManager\DocumentManagerFactory;
use Schema\City\City;
use Schema\Publish\Record\Record;

class RecordModel
{
    private $dm;

    /**
     * PublishModel constructor.
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
     * @param $id
     * @return null|Record
     */
    public function findOneById($id)
    {
        return $this->dm->findOne(['_id' => $id]);
    }

    /**
     * @param City $city
     * @return null|Record
     */
    public function findOneByCity(City $city)
    {
        return $this->dm->findOne(['city' => $city->getShortName()]);
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
}
