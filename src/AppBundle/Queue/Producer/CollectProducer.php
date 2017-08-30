<?php

namespace AppBundle\Queue\Producer;

use AppBundle\Queue\Message\CollectMessage;

class CollectProducer
{
    private $queue;

    const QUEUE = 'queue_collect';

    /**
     * CollectProducer constructor.
     * @param string $host
     * @param string $port
     */
    public function __construct(string $host, string $port)
    {
        $this->queue = (new \Pheanstalk\Pheanstalk($host, $port))->useTube(self::QUEUE);
    }

    /**
     * @param CollectMessage $message
     * @return bool
     */
    public function publish(CollectMessage $message)
    {
        $this->queue->put(serialize($message));

        return true;
    }
}