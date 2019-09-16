<?php

namespace App\Collector;

use App\Exception\ParseFactoryException;
use App\Parser\ParserFactory;
use App\Request\VkPublicRequest;
use Psr\Log\LoggerInterface;
use Schema\Parse\Record\Source;

class CollectorFactory
{
    private $request_vk;
    private $logger;
    private $parser_factory;

    public function __construct(
        VkPublicRequest $request_vk,
        ParserFactory $parser_factory,
        LoggerInterface $logger
    )
    {
        $this->logger         = $logger;
        $this->request_vk     = $request_vk;
        $this->parser_factory = $parser_factory;
    }

    public function init(Source $source): CollectorInterface
    {
        switch ($source->getType()) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentCollector(
                    $this->request_vk,
                    $this->parser_factory,
                    $this->logger

                );

                break;
            case Source::TYPE_VK_WALL:
                return new VkWallCollector(
                    $this->request_vk,
                    $this->parser_factory,
                    $this->logger
                );

                break;
            default:
                throw new ParseFactoryException('Invalid type');
        }
    }
}

