<?php

namespace AppBundle\Model\Document\City;

use Schema\City\City;
use ODM\DocumentManager\DocumentManagerFactory;

class CityModel
{
    /**
     * @var \ODM\DocumentManager\DocumentManager
     */
    private $dm_city;

    /**
     * CityModel constructor.
     * @param DocumentManagerFactory $dm
     */
    public function __construct(DocumentManagerFactory $dm)
    {
        $this->dm_city = $dm->init(City::class);
    }

    /**
     * @return array|City[]
     */
    public function findAll()
    {
        return $this->dm_city->find();
    }

    /**
     * @param $city_id
     * @return null|\ODM\Document\Document
     */
    public function findOneById($city_id)
    {
        return $this->dm_city->findOne(['_id' => $city_id]);
    }

    /**
     * @param City $obj
     * @return City
     */
    public function create(City $obj)
    {
        $this->dm_city->insert($obj);

        return $obj;
    }

    /**
     * @param City $obj
     * @return City
     */
    public function update(City $obj)
    {
        $this->dm_city->update($obj);

        return $obj;
    }
}
