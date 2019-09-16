<?php

namespace App\Parser;

use App\Exception\AppException;
use App\Explorer\Subway\SubwayExplorerFactory;
use App\Explorer\Tomita\TomitaExplorer;
use Schema\Parse\Record\Source;

class ParserFactory
{
    /**
     * @var TomitaExplorer
     */
    private $explorer_tomita;

    /**
     * @var SubwayExplorerFactory
     */
    private $explorer_subway_factory;

    public function __construct(
        TomitaExplorer $explorer_tomita,
        SubwayExplorerFactory $explorer_subway_factory
    )
    {
        $this->explorer_tomita         = $explorer_tomita;
        $this->explorer_subway_factory = $explorer_subway_factory;
    }

    public function init(Source $source, $data): Parser
    {
        switch ($source->getType()) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentParser(
                    $data,
                    $source,
                    $this->explorer_tomita,
                    $this->explorer_subway_factory->init($source->getCity())
                );
                break;
            case Source::TYPE_VK_WALL:
                return new VkWallParser(
                    $data,
                    $source,
                    $this->explorer_tomita,
                    $this->explorer_subway_factory->init($source->getCity())
                );
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

