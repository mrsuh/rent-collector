<?php

namespace AppBundle\Model\Logic\Parser\ContactId;

use AppBundle\Exception\AppException;
use Schema\Parse\Record\Source;

class ContactIdParserFactory
{
    /**
     * @var ContactIdParserInterface[]
     */
    private $instances;

    /**
     * ContactParserFactory constructor.
     */
    public function __construct()
    {
        $this->instances = [];
    }

    /**
     * @param string $type
     * @return ContactIdParserInterface
     */
    public function init(string $type): ContactIdParserInterface
    {
        if (!array_key_exists($type, $this->instances)) {
            $this->instances[$type] = $this->getInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @param string $type
     * @return ContactIdParserInterface
     * @throws AppException
     */
    private function getInstance(string $type)
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentContactIdParser();

                break;
            case Source::TYPE_VK_WALL:
                return new VkWallContactIdParser();

                break;
            case Source::TYPE_AVITO:
                return new AvitoContactIdParser();

                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

