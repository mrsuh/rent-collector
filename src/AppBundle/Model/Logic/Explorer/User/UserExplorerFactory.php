<?php

namespace AppBundle\Model\Logic\Explorer\User;

use AppBundle\Exception\AppException;
use AppBundle\Request\VkPublicRequest;
use Schema\Parse\Record\Source;

class UserExplorerFactory
{
    /**
     * @var VkUserExplorer[]
     */
    private $instances;

    /**
     * @var VkPublicRequest
     */
    private $request;

    /**
     * ContactExplorerFactory constructor.
     * @param VkPublicRequest $request
     */
    public function __construct(VkPublicRequest $request)
    {
        $this->request   = $request;
        $this->instances = [];
    }

    /**
     * @param Source $source
     * @return UserExplorerInterface
     * @throws AppException
     */
    public function init(Source $source)
    {
        $type = $source->getType();
        if (!array_key_exists($type, $this->instances)) {
            $this->instances[$type] = $this->getInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @param string $type
     * @return VkUserExplorer
     * @throws AppException
     */
    private function getInstance(string $type)
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
            case Source::TYPE_VK_WALL:
                return new VkUserExplorer($this->request);
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}