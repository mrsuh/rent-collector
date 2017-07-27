<?php

namespace AppBundle\Model\Logic\Explorer\User;

interface UserExplorerInterface
{
    /**
     * @param int $id
     * @return User
     */
    public function explore(int $id): User;
}