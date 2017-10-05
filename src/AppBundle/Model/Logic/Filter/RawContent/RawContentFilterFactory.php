<?php

namespace AppBundle\Model\Logic\Filter\RawContent;

use AppBundle\Exception\AppException;
use AppBundle\Model\Logic\Explorer\Subway\SubwayExplorerFactory;
use Schema\Parse\Record\Source;

class RawContentFilterFactory
{
    /**
     * @var RawContentFilterInterface[]
     */
    private $instances;

    /**
     * @var SubwayExplorerFactory
     */
    private $explorer_factory;

    /**
     * RawContentFilterFactory constructor.
     */
    public function __construct()
    {
        $this->instances = [];
    }

    /**
     * @param string $type
     * @return RawContentFilterInterface
     */
    public function init(string $type): RawContentFilterInterface
    {
        if (!array_key_exists($type, $this->instances)) {
            $this->instances[$type] = $this->getInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @param string $type
     * @return RawContentFilterInterface
     * @throws AppException
     */
    private function getInstance(string $type)
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentRawContentFilter();
                break;
            case Source::TYPE_VK_WALL:
                return new VkWallRawContentFilter();
                break;
            case Source::TYPE_AVITO:
                return new AvitoRawContentFilter();
                break;

                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

