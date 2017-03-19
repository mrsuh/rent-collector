<?php

namespace AppBundle\ODM;

use MongoDB\Client;

class DBAL
{
    private $db;

    public function __construct($host, $port, $db_name)
    {
        $this->db = (new Client("mongodb://$host:$port"))->$db_name;
    }

    public function insert($table_name, array $data)
    {
        return $this->db->$table_name->insertOne($data);
    }

    public function insertMany($table_name, array $data)
    {
        return $this->db->$table_name->insertMany($data);
    }

    public function update($table_name, array $filter, array $data)
    {
        return $this->db->$table_name->updateOne($filter, ['$set' => $data]);
    }

    public function delete($table_name, array $filter)
    {
        return $this->db->$table_name->deleteOne($filter);
    }

    public function find($table_name, array $filter, array $options = [])
    {
        return $this->db->$table_name->find($filter, $options);
    }

    public function findOne($table_name, array $filter, array $options = [])
    {
        return $this->db->$table_name->findOne($filter, $options);
    }

    public function drop($table_name)
    {
        return $this->db->$table_name->drop();
    }
}

