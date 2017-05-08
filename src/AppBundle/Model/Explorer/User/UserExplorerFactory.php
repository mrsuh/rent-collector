<?php

namespace AppBundle\Model\Explorer\User;

use AppBundle\Document\Note;
use AppBundle\Exception\AppException;
use AppBundle\Request\VkRequest;

class UserExplorerFactory
{
    private $request;

    /**
     * ContactExplorerFactory constructor.
     * @param VkRequest $request
     */
    public function __construct(VkRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @param string $type
     * @return UserExplorerInterface
     * @throws AppException
     */
    public function init(string $type): UserExplorerInterface
    {
        switch ($type) {
            case Note::VK_COMMENT:
            case Note::VK_WALL:
                return new VkUserExplorer($this->request);
                break;
            default:
                throw new AppException('type not found');
        }
    }
}