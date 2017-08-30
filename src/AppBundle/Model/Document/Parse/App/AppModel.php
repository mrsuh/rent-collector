<?php

namespace AppBundle\Model\Document\Parse\App;

use ODM\DocumentManager\DocumentManagerFactory;
use Schema\Parse\App\App;

class AppModel
{
    private $dm;

    /**
     * ParseModel constructor.
     * @param DocumentManagerFactory $dm
     */
    public function __construct(DocumentManagerFactory $dm)
    {
        $this->dm = $dm->init(App::class);
    }

    /**
     * @return array|App[]
     */
    public function findAll()
    {
        return $this->dm->find();
    }

    /**
     * @param $id
     * @return null|App
     */
    public function findOneById($id)
    {
        return $this->dm->findOne(['_id' => $id]);
    }

    /**
     * @param App $obj
     * @return App
     */
    public function create(App $obj)
    {
        $this->dm->insert($obj);

        return $obj;
    }

    /**
     * @param App $obj
     * @return App
     */
    public function update(App $obj)
    {
        $this->dm->update($obj);

        return $obj;
    }
}
