<?php

namespace AppBundle\Queue\Producer;

use AppBundle\Queue\Message\ParseMessage;

class ParseProducer
{
    private $queue;

    const QUEUE = 'queue_parse';

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
     * @param ParseMessage $message
     * @return bool
     */
    public function publish(ParseMessage $message)
    {
        $this->queue->put(serialize($message));

        return true;
    }
}