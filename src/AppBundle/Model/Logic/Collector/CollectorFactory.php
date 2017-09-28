<?php

namespace AppBundle\Model\Logic\Collector;

use AppBundle\Exception\CollectException;
use AppBundle\Exception\ParseFactoryException;
use AppBundle\Model\Document\Parse\App\AppModel;
use AppBundle\Model\Logic\Parser\DateTime\DateTimeParserFactory;
use AppBundle\Model\Logic\Parser\Id\IdParserFactory;
use AppBundle\Model\Logic\Parser\Link\LinkParserFactory;
use AppBundle\Request\AvitoRequest;
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
     * @var AvitoRequest
     */
    private $request_avito;

    /**
     * @var Logger
     */
    private $logger;

    private $parser_id_factory;
    private $parser_link_factory;
    private $parser_datetime_factory;

    /**
     * @var string
     */
    private $dir_tmp;

    /**
     * @var int
     */
    private $last_hours;

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
        AvitoRequest $request_avito,
        AppModel $model_app,
        IdParserFactory $parser_id_factory,
        LinkParserFactory $parser_link_factory,
        DateTimeParserFactory $parser_datetime_factory,
        Logger $logger,
        string $dir_tmp,
        int $last_hours
    )
    {
        $this->instances = [];

        $this->logger     = $logger;
        $this->dir_tmp    = $dir_tmp;
        $this->last_hours = $last_hours;

        $apps = $model_app->findAll();
        $app  = array_key_exists(0, $apps) ? $apps[0] : null;

        if (null === $app) {

            throw new CollectException('There is no app for public request');
        }

        $request_vk->setApp($app);
        $this->request_vk    = $request_vk;
        $this->request_avito = $request_avito;

        $this->parser_id_factory       = $parser_id_factory;
        $this->parser_link_factory     = $parser_link_factory;
        $this->parser_datetime_factory = $parser_datetime_factory;
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
                    $this->parser_id_factory,
                    $this->parser_link_factory,
                    $this->parser_datetime_factory,
                    $this->logger,
                    $this->dir_tmp
                );

                break;
            case Source::TYPE_VK_WALL:
                return new VkWallCollector(
                    $this->request_vk,
                    $this->parser_id_factory,
                    $this->parser_link_factory,
                    $this->parser_datetime_factory,
                    $this->logger,
                    $this->dir_tmp,
                    $this->last_hours
                );

                break;
            case Source::TYPE_AVITO:
                return new AvitoCollector(
                    $this->request_avito,
                    $this->parser_datetime_factory,
                    $this->logger,
                    $this->dir_tmp,
                    $this->last_hours
                );

                break;
            default:
                throw new ParseFactoryException('Invalid type');
        }
    }
}

