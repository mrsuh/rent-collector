<?php

namespace AppBundle\Model\Logic\Parser\Description;

use AppBundle\Exception\AppException;
use Schema\Parse\Record\Source;

class DescriptionParserFactory
{
    /**
     * @var DescriptionParserInterface[]
     */
    private $instances;

    /**
     * DescriptionParserFactory constructor.
     */
    public function __construct()
    {
        $this->instances = [];
    }

    /**
     * @param string $type
     * @return DescriptionParserInterface
     */
    public function init(string $type): DescriptionParserInterface
    {
        if (!array_key_exists($type, $this->instances)) {
            $this->instances[$type] = $this->getInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @param string $type
     * @return DescriptionParserInterface
     * @throws AppException
     */
    private function getInstance(string $type)
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentDescriptionParser();
                break;
            case Source::TYPE_VK_WALL:
                return new VkWallDescriptionParser();
                break;
            case Source::TYPE_AVITO:
                return new AvitoDescriptionParser();
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

