<?php

namespace AppBundle\Model\Logic\Parser\Price;

use AppBundle\Exception\AppException;
use AppBundle\Model\Logic\Explorer\Tomita\TomitaExplorer;
use Schema\Parse\Record\Source;

class PriceParserFactory
{
    /**
     * @var PriceParserInterface[]
     */
    private $instances;

    /**
     * @var TomitaExplorer
     */
    private $explorer;

    /**
     * TypeParserFactory constructor.
     * @param TomitaExplorer $explorer
     */
    public function __construct(TomitaExplorer $explorer)
    {
        $this->instances = [];
        $this->explorer  = $explorer;
    }

    /**
     * @param string $type
     * @return PriceParserInterface
     */
    public function init(string $type): PriceParserInterface
    {
        if (!array_key_exists($type, $this->instances)) {
            $this->instances[$type] = $this->getInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @param string $type
     * @return PriceParserInterface
     * @throws AppException
     */
    private function getInstance(string $type)
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentPriceParser($this->explorer);
                break;
            case Source::TYPE_VK_WALL:
                return new VkWallPriceParser($this->explorer);
                break;
            case Source::TYPE_AVITO:
                return new AvitoPriceParser();
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

