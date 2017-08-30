<?php

namespace AppBundle\Queue\Producer;

use AppBundle\Queue\Message\PublishMessage;

class PublishProducer
{
    private $queue;

    const QUEUE = 'queue_publish';

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
     * @param PublishMessage $message
     * @return bool
     */
    public function publish(PublishMessage $message)
    {
        //$this->queue->put(serialize($message));

        return true;
    }
}