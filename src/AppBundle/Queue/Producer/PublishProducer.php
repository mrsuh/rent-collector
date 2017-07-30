<?php

namespace AppBundle\Queue\Producer;

use AppBundle\Queue\Consumer\PublishConsumer;
use AppBundle\Queue\Message\PublishMessage;

class PublishProducer
{
    private $consumer;

    /**
     * PublishProducer constructor.
     * @param PublishConsumer $consumer
     */
    public function __construct(PublishConsumer $consumer)
    {
        $this->consumer = $consumer;
    }

    /**
     * @param PublishMessage $message
     * @return bool
     */
    public function publish(PublishMessage $message)
    {
        $this->consumer->handle($message);

        return true;
    }
}