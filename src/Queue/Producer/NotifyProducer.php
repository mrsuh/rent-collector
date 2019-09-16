<?php

namespace App\Queue\Producer;

use App\Queue\Message\NotifyMessage;
use Pheanstalk\Contract\PheanstalkInterface;

class NotifyProducer
{
    const QUEUE = 'queue_notify';
    private $queue;

    public function __construct(PheanstalkInterface $client)
    {
        $this->queue = $client;
    }

    /**
     * @param NotifyMessage $message
     * @return bool
     */
    public function publish(NotifyMessage $message)
    {
        $this->queue->useTube(self::QUEUE)->put(serialize($message));

        return true;
    }
}