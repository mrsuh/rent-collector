<?php

namespace AppBundle\Model\Explorer\User;

use AppBundle\Document\Note;
use AppBundle\Exception\AppException;
use AppBundle\Request\VkPublicRequest;

class UserExplorerFactory
{
    private $request;

    /**
     * ContactExplorerFactory constructor.
     * @param VkPublicRequest $request
     */
    public function __construct(VkPublicRequest $request)
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