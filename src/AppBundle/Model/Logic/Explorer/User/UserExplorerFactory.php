<?php

namespace AppBundle\Model\Logic\Explorer\User;

use AppBundle\Exception\AppException;
use AppBundle\Request\VkPublicRequest;
use Schema\ParseList\Source;

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
            case Source::TYPE_VK_COMMENT:
            case Source::TYPE_VK_WALL:
                return new VkUserExplorer($this->request);
                break;
            default:
                throw new AppException('type not found');
        }
    }
}