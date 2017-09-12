<?php

namespace AppBundle\Model\Logic\Explorer\User;

class User
{
    private $name;
    private $blacklisted;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBlacklisted()
    {
        return $this->blacklisted;
    }

    /**
     * @param $blacklisted
     * @return $this
     */
    public function setBlacklisted($blacklisted)
    {
        $this->blacklisted = $blacklisted;

        return $this;
    }
}