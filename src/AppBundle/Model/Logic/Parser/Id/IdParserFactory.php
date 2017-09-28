<?php

namespace AppBundle\Model\Logic\Parser\Id;

use AppBundle\Exception\AppException;
use Schema\Parse\Record\Source;

class IdParserFactory
{
    /**
     * @var IdParserInterface[]
     */
    private $instances;

    /**
     * IdParserFactory constructor.
     */
    public function __construct()
    {
        $this->instances = [];
    }

    /**
     * @param string $type
     * @return IdParserInterface
     */
    public function init(string $type): IdParserInterface
    {
        if (!array_key_exists($type, $this->instances)) {
            $this->instances[$type] = $this->getInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @param string $type
     * @return VkCommentIdParser|VkWallIdParser
     * @throws AppException
     */
    private function getInstance(string $type)
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentIdParser();

                break;
            case Source::TYPE_VK_WALL:
                return new VkWallIdParser();

                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

