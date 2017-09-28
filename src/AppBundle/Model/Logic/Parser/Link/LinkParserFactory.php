<?php

namespace AppBundle\Model\Logic\Parser\Link;

use AppBundle\Exception\AppException;
use Schema\Parse\Record\Source;

class LinkParserFactory
{
    /**
     * @var LinkParserInterface[]
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
     * @return LinkParserInterface
     */
    public function init(string $type): LinkParserInterface
    {
        if (!array_key_exists($type, $this->instances)) {
            $this->instances[$type] = $this->getInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @param string $type
     * @return VkCommentLinkParser|VkWallLinkParser
     * @throws AppException
     */
    private function getInstance(string $type)
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentLinkParser();

                break;
            case Source::TYPE_VK_WALL:
                return new VkWallLinkParser();

                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

