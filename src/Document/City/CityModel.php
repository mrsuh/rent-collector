<?php

namespace App\Document\City;

use ODM\DocumentManager\DocumentManagerFactory;
use Schema\City\City;

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
     * @return City[]
     */
    public function findAll()
    {
        return $this->dm_city->find();
    }

    /**
     * @param $city_id
     * @return null|City
     */
    public function findOneById($city_id)
    {
        return $this->dm_city->findOne(['_id' => $city_id]);
    }

    /**
     * @param string $short_name
     * @return null|City
     */
    public function findOneByShortName(string $short_name)
    {
        return $this->dm_city->findOne(['short_name' => $short_name]);
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
