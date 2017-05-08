<?php

namespace AppBundle\Model\Parser\Contact;

class Contact
{
    private $id;
    private $link;
    private $write;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param $link
     * @return $this
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWrite()
    {
        return $this->write;
    }

    /**
     * @param $write
     * @return $this
     */
    public function setWrite($write)
    {
        $this->write = $write;

        return $this;
    }
}

