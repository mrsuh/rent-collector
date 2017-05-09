<?php

namespace AppBundle\Model\Explorer\User;

interface UserExplorerInterface
{
    /**
     * @param int $id
     * @return User
     */
    public function explore(int $id): User;
}