<?php

namespace AppBundle\Model\Logic\Parser\Contact;

use AppBundle\Exception\AppException;
use Schema\Parse\Record\Source;

class ContactParserFactory
{
    /**
     * @var ContactParserInterface[]
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
     * @param Source $source
     * @return ContactParserInterface
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
     * @return VkCommentContactParser|VkWallContactParser
     * @throws AppException
     */
    private function getInstance(string $type)
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentContactParser();

                break;
            case Source::TYPE_VK_WALL:
                return new VkWallContactParser();

                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

