<?php

namespace App\Queue\Producer;

use App\Queue\Message\CollectMessage;
use Pheanstalk\Contract\PheanstalkInterface;

class CollectProducer
{
    const QUEUE = 'queue_collect';
    private $queue;

    public function __construct(PheanstalkInterface $client)
    {
        $this->queue = $client;
    }

    /**
     * @param CollectMessage $message
     * @return bool
     */
    public function publish(CollectMessage $message)
    {
        $this->queue->useTube(self::QUEUE)->put(serialize($message));

        return true;
    }
}