<?php

namespace AppBundle\ODM\Document;

class Note extends Document
{
    const ROOM   = 0;
    const FLAT_1 = 1;
    const FLAT_2 = 2;
    const FLAT_3 = 3;
    const FLAT_N = 4;
    const STUDIO = 5;

    const VK_COMMENT = 'vk.com:comment';
    const VK_WALL    = 'vk.com:wall';

    private $id;

    private $external_id;

    private $source;

    private $city;

    private $community;

    private $type;

    private $photos;

    private $price;

    private $area;

    private $contacts;

    private $timestamp;

    private $subways;

    private $description;

    private $description_hash;

    private $active;

    private $deleted;

    public function __construct()
    {
        $this->id        = uniqid();
        $this->active    = false;
        $this->deleted = false;
        $this->photos    = [];
        $this->contacts  = ['phones' => [], 'person' => ['name' => null, 'link' => null, 'write' => null]];
        $this->subways   = [];
        $this->community = ['name' => null, 'link' => null];
    }

    /**
     * @return string
     */
    public function getId()
    {
        return (string)$this->id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getPhotos(): array
    {
        return $this->photos;
    }

    /**
     * @param array $photos
     * @return $this
     */
    public function setPhotos(array $photos)
    {
        $this->photos = $photos;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param $price
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * @param $area
     * @return $this
     */
    public function setArea($area)
    {
        $this->area = $area;

        return $this;
    }

    /**
     * @return array
     */
    public function getContacts(): array
    {
        return $this->contacts;
    }

    /**
     * @param array $contacts
     * @return $this
     */
    public function setContacts(array $contacts)
    {
        $this->contacts = $contacts;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param $timestamp
     * @return $this
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * @return array
     */
    public function getSubways(): array
    {
        return $this->subways;
    }

    /**
     * @param array $subways
     * @return $this
     */
    public function setSubways(array $subways)
    {
        $this->subways = $subways;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description      = $description;
        $this->description_hash = md5($description);

        return $this;
    }

    /**
     * @return array
     */
    public function getCommunity(): array
    {
        return $this->community;
    }

    /**
     * @param array $community
     * @return $this
     */
    public function setCommunity(array $community)
    {
        $this->community = $community;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getExternalId()
    {
        return $this->external_id;
    }

    /**
     * @param $external_id
     * @return $this
     */
    public function setExternalId($external_id)
    {
        $this->external_id = $external_id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param $city
     * @return $this
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescriptionHash()
    {
        return $this->description_hash;
    }

    /**
     * @param $description_hash
     * @return $this
     */
    public function setDescriptionHash($description_hash)
    {
        $this->description_hash = $description_hash;

        return $this;
    }

    /**
     * @return bool
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return $this
     */
    public function setActive(bool $active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     * @return $this
     */
    public function setDeleted(bool $deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }
}