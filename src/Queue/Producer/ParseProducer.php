<?php

namespace App\Queue\Producer;

use App\Queue\Message\ParseMessage;
use Pheanstalk\Contract\PheanstalkInterface;

class ParseProducer
{
    const QUEUE = 'queue_parse';
    private $queue;

    public function __construct(PheanstalkInterface $client)
    {
        $this->queue = $client;
    }

    /**
     * @param ParseMessage $message
     * @return bool
     */
    public function publish(ParseMessage $message)
    {
        $this->queue->useTube(self::QUEUE)->put(serialize($message));

        return true;
    }
}