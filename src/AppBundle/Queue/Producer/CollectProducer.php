<?php

namespace AppBundle\Queue\Producer;

use AppBundle\Queue\Consumer\CollectConsumer;
use AppBundle\Queue\Message\CollectMessage;

class CollectProducer
{
    private $consumer;

    /**
     * CollectProducer constructor.
     * @param CollectConsumer $consumer
     */
    public function __construct(CollectConsumer $consumer)
    {
        $this->consumer = $consumer;
    }

    /**
     * @param CollectMessage $message
     * @return bool
     */
    public function publish(CollectMessage $message)
    {
        $this->consumer->handle($message);

        return true;
    }
}