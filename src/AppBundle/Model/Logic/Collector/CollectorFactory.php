<?php

namespace AppBundle\Model\Logic\Collector;

use AppBundle\Exception\ParseFactoryException;
use AppBundle\Request\VkPublicRequest;
use Monolog\Logger;
use Schema\ParseList\Source;

class CollectorFactory
{
    private $request;
    private $logger;
    private $dir_tmp;

    /**
     * CollectorFactory constructor.
     * @param VkPublicRequest $request
     * @param Logger          $logger
     * @param string          $dir_tmp
     */
    public function __construct(VkPublicRequest $request, Logger $logger, string $dir_tmp)
    {
        $this->request = $request;
        $this->logger  = $logger;
        $this->dir_tmp = $dir_tmp;
    }

    /**
     * @param string $type
     * @return CollectorInterface
     * @throws ParseFactoryException
     */
    public function init(string $type): CollectorInterface
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentCollector($this->request, $this->logger, $this->dir_tmp);

                break;
            case Source::TYPE_VK_WALL:
                return new VkWallCollector($this->request, $this->logger, $this->dir_tmp);

                break;
            default:
                throw new ParseFactoryException('Invalid parser source');
        }
    }
}

