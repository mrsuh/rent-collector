<?php

namespace AppBundle\Model\Document\City;

use Schema\City\Subway;
use ODM\DocumentManager\DocumentManagerFactory;

class SubwayModel
{
    /**
     * @var \ODM\DocumentManager\DocumentManager
     */
    private $dm_subway;

    /**
     * CityModel constructor.
     * @param DocumentManagerFactory $dm
     */
    public function __construct(DocumentManagerFactory $dm)
    {
        $this->dm_subway = $dm->init(Subway::class);
    }

    /**
     * @return array|Subway[]
     */
    public function findAll()
    {
        return $this->dm_subway->find();
    }
}
