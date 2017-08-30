<?php

namespace AppBundle\Model\Logic\Parser\Photo;

use AppBundle\Exception\AppException;
use Schema\Parse\Record\Source;

class PhotoParserFactory
{
    /**
     * @var PhotoParserInterface[]
     */
    private $instances;

    /**
     * PhotoParserFactory constructor.
     */
    public function __construct()
    {
        $this->instances = [];
    }

    /**
     * @param Source $source
     * @return PhotoParserInterface
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
     * @return VkCommentPhotoParser|VkWallPhotoParser
     * @throws AppException
     */
    private function getInstance(string $type)
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentPhotoParser();
                break;
            case Source::TYPE_VK_WALL:
                return new VkWallPhotoParser();
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

