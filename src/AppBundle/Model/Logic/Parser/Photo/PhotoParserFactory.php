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
     * @param string $type
     * @return PhotoParserInterface
     */
    public function init(string $type): PhotoParserInterface
    {
        if (!array_key_exists($type, $this->instances)) {
            $this->instances[$type] = $this->getInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @param string $type
     * @return PhotoParserInterface
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
            case Source::TYPE_AVITO:
                return new AvitoPhotoParser();
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

