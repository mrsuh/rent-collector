<?php

namespace AppBundle\Model\Collector;

use AppBundle\Exception\ParseFactoryException;
use AppBundle\ODM\Document\Note;
use AppBundle\Service\Client\Http;

class CollectorFactory
{
    private $http_client;
    private $dir_tmp;

    public function __construct(Http $http_client, $dir_tmp)
    {
        $this->http_client = $http_client;
        $this->dir_tmp     = $dir_tmp;
    }

    public function init($type)
    {
        switch ($type) {
            case Note::VK_COMMENT:
                return new VkCommentCollector($this->http_client, $this->dir_tmp);
                break;
            case Note::VK_WALL:
                return new VkWallCollector($this->http_client, $this->dir_tmp);
                break;
            default:
                throw new ParseFactoryException('Invalid parser source');
        }
    }
}

