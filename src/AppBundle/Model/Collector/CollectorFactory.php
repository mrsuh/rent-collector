<?php

namespace AppBundle\Model\Collector;

use AppBundle\Exception\ParseFactoryException;
use AppBundle\Document\Note;
use AppBundle\Request\VkRequest;

class CollectorFactory
{
    private $request;
    private $dir_tmp;

    /**
     * CollectorFactory constructor.
     * @param VkRequest $request
     * @param string    $dir_tmp
     */
    public function __construct(VkRequest $request, string $dir_tmp)
    {
        $this->request = $request;
        $this->dir_tmp = $dir_tmp;
    }

    public function init(string $type): CollectorInterface
    {
        switch ($type) {
            case Note::VK_COMMENT:
                return new VkCommentCollector($this->request, $this->dir_tmp);

                break;
            case Note::VK_WALL:
                return new VkWallCollector($this->request, $this->dir_tmp);

                break;
            default:
                throw new ParseFactoryException('Invalid parser source');
        }
    }
}

