<?php

namespace AppBundle\Model\Logic\Collector;

use AppBundle\Exception\CollectException;
use AppBundle\Exception\ParseFactoryException;
use AppBundle\Model\Document\Parse\App\AppModel;
use AppBundle\Model\Logic\Parser\ParserFactory;
use AppBundle\Request\VkPublicRequest;
use Monolog\Logger;
use Schema\Parse\Record\Source;

class CollectorFactory
{
    /**
     * @var CollectorInterface[]
     */
    private $instances;

    /**
     * @var VkPublicRequest
     */
    private $request_vk;

    /**
     * @var Logger
     */
    private $logger;

    private $parser_factory;

    /**
     * @var string
     */
    private $dir_tmp;

    /**
     * @var string
     */
    private $period;

    /**
     * CollectorFactory constructor.
     * @param VkPublicRequest $request
     * @param AppModel        $model
     * @param Logger          $logger
     * @param string          $dir_tmp
     * @throws CollectException
     */
    public function __construct(
        VkPublicRequest $request_vk,
        AppModel $model_app,
        ParserFactory $parser_factory,
        Logger $logger,
        string $dir_tmp,
        string $period
    )
    {
        $this->instances = [];

        $this->logger  = $logger;
        $this->dir_tmp = $dir_tmp;
        $this->period  = $period;

        $this->request_vk    = $request_vk;

        $this->parser_factory = $parser_factory;
    }

    /**
     * @param Source $source
     * @return CollectorInterface
     */
    public function init(Source $source)
    {
        $type = $source->getType();

        if (!array_key_exists($type, $this->instances)) {
            $this->instances[$type] = $this->getInstance($source);
        }

        return $this->instances[$type];
    }

    /**
     * @param Source $source
     * @return CollectorInterface
     * @throws ParseFactoryException
     */
    private function getInstance(Source $source)
    {
        switch ($source->getType()) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentCollector(
                    $this->request_vk,
                    $this->parser_factory,
                    $this->logger,
                    $this->dir_tmp,
                    $this->period
                );

                break;
            case Source::TYPE_VK_WALL:
                return new VkWallCollector(
                    $this->request_vk,
                    $this->parser_factory,
                    $this->logger,
                    $this->dir_tmp,
                    $this->period
                );

                break;
            default:
                throw new ParseFactoryException('Invalid type');
        }
    }
}

