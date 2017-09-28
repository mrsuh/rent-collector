<?php

namespace AppBundle\Model\Logic\Parser\ContactName;

use AppBundle\Exception\AppException;
use AppBundle\Model\Logic\Explorer\User\UserExplorerFactory;
use Schema\Parse\Record\Source;

class ContactNameParserFactory
{
    /**
     * @var ContactNameParserInterface[]
     */
    private $instances;

    /**
     *
     */
    private $explorer;

    /**
     * ContactParserFactory constructor.
     */
    public function __construct(UserExplorerFactory $explorer_user_factory)
    {
        $this->instances = [];
        $this->explorer  = $explorer_user_factory->init(Source::TYPE_VK_WALL);
    }

    /**
     * @param string $type
     * @return ContactNameParserInterface
     */
    public function init(string $type): ContactNameParserInterface
    {
        if (!array_key_exists($type, $this->instances)) {
            $this->instances[$type] = $this->getInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @param string $type
     * @return ContactNameParserInterface
     * @throws AppException
     */
    private function getInstance(string $type)
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentContactNameParser($this->explorer);
                break;
            case Source::TYPE_VK_WALL:
                return new VkWallContactNameParser($this->explorer);
                break;
            case Source::TYPE_AVITO:
                return new AvitoContactNameParser();
                break;

                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

