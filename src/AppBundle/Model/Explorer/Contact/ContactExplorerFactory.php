<?php

namespace AppBundle\Model\Explorer\Contact;

use AppBundle\ODM\Document\Note;
use AppBundle\Service\Client\Http;

class ContactExplorerFactory
{
    private $client;

    /**
     * ContactExplorerFactory constructor.
     * @param Http $client
     */
    public function __construct(Http $client)
    {
        $this->client = $client;
    }

    /**
     * @param $type
     * @return VkCommentContactExplorer|VkWallContactExplorer|null
     */
    public function init($type)
    {
        switch ($type) {
            case Note::VK_COMMENT:
                return new VkCommentContactExplorer($this->client);
                break;
            case Note::VK_WALL:
                return new VkWallContactExplorer($this->client);
                break;
        }

        return null;
    }
}