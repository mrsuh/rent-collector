<?php

namespace AppBundle\Model\Document\ParseList;

use Schema\ParseList\Record;
use ODM\DocumentManager\DocumentManagerFactory;

class SourceModel
{
    private $dm_parse;

    /**
     * ParseModel constructor.
     * @param DocumentManagerFactory $dm
     */
    public function __construct(DocumentManagerFactory $dm)
    {
        $this->dm_parse = $dm->init(Record::class);
    }

    /**
     * @param $id
     * @return null|Record
     */
    public function findOneById($id)
    {
        return $this->dm_parse->findOne(['_id' => $id]);
    }

    /**
     * @param Record $obj
     * @return Record
     */
    public function create(Record $obj)
    {
        $this->dm_parse->insert($obj);

        return $obj;
    }

    /**
     * @param Record $obj
     * @return Record
     */
    public function update(Record $obj)
    {
        $this->dm_parse->update($obj);

        return $obj;
    }
}
