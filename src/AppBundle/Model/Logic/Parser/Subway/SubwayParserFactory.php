<?php

namespace AppBundle\Model\Logic\Parser\Subway;

use AppBundle\Exception\AppException;
use AppBundle\Model\Logic\Explorer\Subway\SubwayExplorerFactory;
use Schema\Parse\Record\Source;

class SubwayParserFactory
{
    /**
     * @var SubwayParserInterface[]
     */
    private $instances;

    /**
     * @var SubwayExplorerFactory
     */
    private $explorer_factory;

    /**
     * SubwayParserFactory constructor.
     * @param SubwayExplorerFactory $explorer_factory
     */
    public function __construct(SubwayExplorerFactory $explorer_factory)
    {
        $this->instances        = [];
        $this->explorer_factory = $explorer_factory;
    }

    /**
     * @param string $type
     * @return SubwayParserInterface
     */
    public function init(string $type): SubwayParserInterface
    {
        if (!array_key_exists($type, $this->instances)) {
            $this->instances[$type] = $this->getInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @param string $type
     * @return SubwayParserInterface
     * @throws AppException
     */
    private function getInstance(string $type)
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentSubwayParser($this->explorer_factory);
                break;
            case Source::TYPE_VK_WALL:
                return new VkWallSubwayParser($this->explorer_factory);
                break;
            case Source::TYPE_AVITO:
                return new AvitoSubwayParser($this->explorer_factory);
                break;

                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

