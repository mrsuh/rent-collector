<?php

namespace AppBundle\ODM\DocumentMapper;

use AppBundle\ODM\DBAL;
use AppBundle\ODM\Document\Document;

class DataMapper
{
    private $dbal;

    private $table_name;
    private $class;

    const MONGO_ID = '_id';
    const ID       = 'id';

    public function __construct(DBAL $dbal, $class)
    {
        $this->dbal       = $dbal;
        $this->class      = $class;
        $path             = explode('\\', $class);
        $this->table_name = mb_strtolower($this->camelCaseToSnake(array_pop($path)));
    }

    public function insert(Document $obj)
    {
        $data = $this->objToArray($obj);

        if (array_key_exists(self::MONGO_ID, $data) && empty($data[self::MONGO_ID])) {
            unset($data[self::MONGO_ID]);
        }

        $result = $this->dbal->insert($this->table_name, $data);

        return $obj->setId($result->getInsertedId());
    }

    public function update(Document $obj)
    {
        $data = $this->objToArray($obj);
        unset($data[self::MONGO_ID]);

        $this->dbal->update($this->table_name, [self::MONGO_ID => $obj->getId()], $data);

        return $obj;
    }

    public function delete(Document $obj)
    {
        return $this->dbal->delete($this->table_name, [self::MONGO_ID => $obj->getId()]);
    }

    public function drop()
    {
        return $this->dbal->drop($this->table_name);
    }

    public function find(array $filter = [], array $options = [])
    {
        if (array_key_exists(self::ID, $filter)) {
            $filter[self::MONGO_ID] = $filter[self::ID];
            unset($filter[self::ID]);
        }

        $result = [];
        foreach ($this->dbal->find($this->table_name, $filter, $options) as $r) {
            $result[] = $this->mapObj(new $this->class, $r);
        }

        return $result;
    }

    public function findOne(array $filter = [], array $options = [])
    {
        if (array_key_exists(self::ID, $filter)) {
            $filter[self::MONGO_ID] = $filter[self::ID];
            unset($filter[self::ID]);
        }

        $data = $this->dbal->findOne($this->table_name, $filter, $options);

        return empty($data) ? null : $this->mapObj(new $this->class, $data);
    }

    private function mapObj($obj, $data)
    {
        $methods = get_class_methods(get_class($obj));

        foreach ((array)$data as $field => $value) {

            $field_name = self::MONGO_ID === (string)$field ? self::ID : $field;
            $value      = self::MONGO_ID === (string)$field ? (string)$value : $value;
            $value      = is_object($value) ? (array)$value : $value;

            if (null === $value) {
                continue;
            }

            $setter = 'set' . $this->snakeToCamelCase($field_name);

            if (!in_array($setter, $methods)) {
                continue;
            }

            $obj->$setter($value);
        }

        return $obj;
    }

    private function snakeToCamelCase($input)
    {
        $new = [];
        foreach (explode('_', $input) as $key => $word) {
            $new[] = ucfirst($word);
        }

        return implode('', $new);
    }

    private function camelCaseToSnake($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $res = $matches[0];
        foreach ($res as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode('_', $res);
    }

    public function objToArray($obj)
    {
        $data    = [];
        $reflect = new \ReflectionClass($obj);
        foreach ($reflect->getProperties() as $prop) {
            $getter = 'get' . $this->snakeToCamelCase($prop->getName());

            if ($prop->getName() === self::ID) {
                $prop_name = self::MONGO_ID;
            } else {
                $prop_name = $prop->getName();
            }

            $data[$prop_name] = $obj->$getter();
        }

        return $data;
    }
}