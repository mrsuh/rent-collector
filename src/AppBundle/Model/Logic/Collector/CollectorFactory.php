<?php

namespace AppBundle\Model\Logic\Collector;

use AppBundle\Exception\CollectException;
use AppBundle\Exception\ParseFactoryException;
use AppBundle\Model\Document\Parse\App\AppModel;
use AppBundle\Model\Logic\Publisher\VkPublisher;
use AppBundle\Request\Client;
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
    private $request;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $dir_tmp;

    /**
     * @var AppModel
     */
    private $model_app;

    /**
     * CollectorFactory constructor.
     * @param VkPublicRequest $request
     * @param AppModel        $model
     * @param Logger          $logger
     * @param string          $dir_tmp
     * @throws CollectException
     */
    public function __construct(VkPublicRequest $request, AppModel $model_app, Logger $logger, string $dir_tmp)
    {
        $this->instances = [];

        $this->logger  = $logger;
        $this->dir_tmp = $dir_tmp;

        $apps = $model_app->findAll();
        $app  = array_key_exists(0, $apps) ? $apps[0] : null;

        if (null === $app) {

            throw new CollectException('There is no app for public request');
        }

        $request->setApp($app);
        $this->request = $request;
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
     * @return VkCommentCollector|VkWallCollector
     * @throws ParseFactoryException
     */
    private function getInstance(Source $source)
    {

        switch ($source->getType()) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentCollector($this->request, $this->logger, $this->dir_tmp);

                break;
            case Source::TYPE_VK_WALL:
                return new VkWallCollector($this->request, $this->logger, $this->dir_tmp);

                break;
            default:
                throw new ParseFactoryException('Invalid type');
        }
    }
}

