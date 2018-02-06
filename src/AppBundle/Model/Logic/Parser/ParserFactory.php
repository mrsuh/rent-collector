<?php

namespace AppBundle\Model\Logic\Parser;

use AppBundle\Exception\AppException;
use AppBundle\Model\Logic\Explorer\Subway\SubwayExplorerFactory;
use AppBundle\Model\Logic\Explorer\Tomita\TomitaExplorer;
use AppBundle\Model\Logic\Explorer\User\UserExplorerFactory;
use AppBundle\Model\Logic\Explorer\User\VkUserExplorer;
use Schema\Parse\Record\Source;

class ParserFactory
{
    /**
     * @var UserExplorerFactory
     */
    private $explorer_user_factory;

    /**
     * @var TomitaExplorer
     */
    private $explorer_tomita;

    /**
     * @var SubwayExplorerFactory
     */
    private $explorer_subway_factory;

    /**
     * ParserFactory constructor.
     * @param TomitaExplorer        $explorer_tomita
     * @param SubwayExplorerFactory $explorer_subway_factory
     * @param UserExplorerFactory   $explorer_user_factory
     */
    public function __construct(
        TomitaExplorer $explorer_tomita,
        SubwayExplorerFactory $explorer_subway_factory,
        UserExplorerFactory $explorer_user_factory
    )
    {
        $this->explorer_tomita         = $explorer_tomita;
        $this->explorer_subway_factory = $explorer_subway_factory;
        $this->explorer_user_factory   = $explorer_user_factory;
    }

    /**
     * @param string $type
     * @param        $data
     * @return Parser
     * @throws AppException
     */
    public function init(Source $source, $data): Parser
    {
        switch ($source->getType()) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentParser(
                    $data,
                    $source,
                    $this->explorer_tomita,
                    $this->explorer_subway_factory->init($source->getCity()),
                    $this->explorer_user_factory->init($source->getType())
                );
                break;
            case Source::TYPE_VK_WALL:
                return new VkWallParser(
                    $data,
                    $source,
                    $this->explorer_tomita,
                    $this->explorer_subway_factory->init($source->getCity()),
                    $this->explorer_user_factory->init($source->getType())
                );
                break;
            case Source::TYPE_AVITO:
                return new AvitoParser(
                    $data,
                    $source,
                    $this->explorer_subway_factory->init($source->getCity())
                );
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

