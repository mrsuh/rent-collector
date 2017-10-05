<?php

namespace AppBundle\Queue\Consumer;

use AppBundle\Model\Document\City\CityModel;
use AppBundle\Model\Document\Note\NoteModel;
use AppBundle\Model\Document\Publish\Record\RecordModel;
use AppBundle\Model\Logic\Publisher\PublisherFactory;
use AppBundle\Queue\Message\PublishMessage;
use Monolog\Logger;
use Schema\City\City;

class PublishConsumer
{
    private $logger;
    private $model_record;
    private $model_note;
    private $model_city;
    private $publisher_factory;

    /**
     * PublishConsumer constructor.
     * @param PublisherFactory $publisher_factory
     * @param RecordModel      $model_record
     * @param NoteModel        $model_note
     * @param Logger           $logger
     */
    public function __construct(
        PublisherFactory $publisher_factory,
        RecordModel $model_record,
        NoteModel $model_note,
        CityModel $model_city,
        Logger $logger
    )
    {
        $this->model_record      = $model_record;
        $this->model_note        = $model_note;
        $this->model_city        = $model_city;
        $this->logger            = $logger;
        $this->publisher_factory = $publisher_factory;
    }

    /**
     * @param PublishMessage $message
     * @return bool
     */
    public function handle(PublishMessage $message)
    {
        $id        = $message->getNote()->getId();
        $city_name = $message->getNote()->getCity();
        try {

            $this->logger->debug('Handle message', [
                'id'   => $id,
                'city' => $city_name
            ]);

            $city = $this->model_city->findOneByShortName($message->getSource()->getCity());

            $note = $message->getNote();

            if (empty($note->getSubways()) && $city->hasSubway()) {
                $this->logger->debug('There are no subways', [
                    'id'   => $id,
                    'city' => $city_name
                ]);

                return false;
            }

            if (empty($note->getPrice())) {
                $this->logger->debug('There is no price', [
                    'id'   => $id,
                    'city' => $city_name
                ]);

                return false;
            }

            if (count($note->getPhotos()) < 3) {

                $this->logger->debug('There are no photos', [
                    'id'   => $id,
                    'city' => $city_name
                ]);

                return false;
            }

            $record = $this->model_record->findOneByCity((new City())->setShortName($message->getSource()->getCity()));

            if (!$record->isActive()) {
                $this->logger->info('Publish record is not active for city', [
                    'id'   => $id,
                    'city' => $city_name
                ]);

                return false;
            }

            if (null === $record) {
                $this->logger->error('There is no publish record for city', [
                    'id'   => $id,
                    'city' => $city_name
                ]);

                return false;
            }

            $publisher = $this->publisher_factory->init($record);

            if (!$publisher->publish($note)) {

                $this->logger->error('Error publish message', [
                    'id'   => $id,
                    'city' => $city_name
                ]);

                return false;
            }

            $note
                ->setPublished(true)
                ->setPublishedTimestamp((new \DateTime())->getTimestamp());

            $this->model_note->update($note);

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