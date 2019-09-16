<?php

namespace App\Queue\Consumer;

use App\Document\City\CityModel;
use App\Queue\Message\NotifyMessage;
use App\Request\NotifierRequest;
use Psr\Log\LoggerInterface;

class NotifyConsumer
{
    private $model_city;
    private $request_notifier;
    private $logger;

    public function __construct(
        CityModel $model_city,
        NotifierRequest $request_notifier,
        LoggerInterface $logger
    )
    {
        $this->model_city       = $model_city;
        $this->request_notifier = $request_notifier;
        $this->logger           = $logger;
    }

    public function handle(NotifyMessage $message)
    {
        $note      = $message->getNote();
        $id        = $note->getId();
        $city_name = $note->getCity();

        try {

            $this->logger->debug('Handling message...', [
                'id'   => $id,
                'city' => $city_name
            ]);

            $city = $this->model_city->findOneByShortName($note->getCity());

            $this->request_notifier->notify($city, $message->getNote());

        } catch (\Exception $e) {
            $this->logger->error('Handle error', [
                'id'        => $id,
                'city'      => $city_name,
                'exception' => $e->getMessage()
            ]);
        }

        return true;
    }
}