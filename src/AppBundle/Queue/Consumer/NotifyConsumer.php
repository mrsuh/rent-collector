<?php

namespace AppBundle\Queue\Consumer;

use AppBundle\Queue\Message\NotifyMessage;
use AppBundle\Request\NotifierRequest;
use Monolog\Logger;

class NotifyConsumer
{
    private $mailer;
    private $request_notifier;
    private $logger;

    /**
     * NotifyConsumer constructor.
     * @param \Swift_Mailer $mailer
     * @param Logger        $logger
     */
    public function __construct(
        \Swift_Mailer $mailer,
        NotifierRequest $request_notifier,
        Logger $logger
    )
    {
        $this->mailer           = $mailer;
        $this->request_notifier = $request_notifier;
        $this->logger           = $logger;
    }

    /**
     * @param NotifyMessage $message
     * @return bool
     */
    public function handle(NotifyMessage $message)
    {
        $note = $message->getNote();
        $id   = $note->getId();
        $city = $note->getCity();

        try {

            $this->logger->debug('Handling message...', [
                'id'   => $id,
                'city' => $city
            ]);

            $this->request_notifier->notify($message->getNote());

        } catch (\Exception $e) {
            $this->logger->error('Handle error', [
                'id'        => $id,
                'city'      => $city,
                'exception' => $e->getMessage()
            ]);
        }

        return true;
    }
}