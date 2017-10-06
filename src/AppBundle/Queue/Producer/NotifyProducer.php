<?php

namespace AppBundle\Queue\Producer;

use AppBundle\Queue\Message\NotifyMessage;

class NotifyProducer
{
    private $queue;

    const QUEUE = 'queue_notify';

    /**
     * NotifyProducer constructor.
     * @param string $host
     * @param string $port
     */
    public function __construct(string $host, string $port)
    {
        $this->queue = (new \Pheanstalk\Pheanstalk($host, $port))->useTube(self::QUEUE);
    }

    /**
     * @param NotifyMessage $message
     * @return bool
     */
    public function publish(NotifyMessage $message)
    {
        $this->queue->put(serialize($message));

        return true;
    }
}