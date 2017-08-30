<?php

namespace AppBundle\Queue\Consumer;

use AppBundle\Model\Document\Note\NoteModel;
use AppBundle\Model\Document\Publish\Record\RecordModel;
use AppBundle\Model\Logic\Publisher\PublisherFactory;
use AppBundle\Queue\Message\CollectMessage;
use AppBundle\Queue\Message\PublishMessage;
use Monolog\Logger;
use Schema\City\City;

class PublishConsumer
{
    private $logger;
    private $model_record;
    private $model_note;
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
        Logger $logger
    )
    {
        $this->model_record      = $model_record;
        $this->model_note        = $model_note;
        $this->logger            = $logger;
        $this->publisher_factory = $publisher_factory;
    }

    /**
     * @param PublishMessage $message
     * @return bool
     */
    public function handle(PublishMessage $message)
    {
        try {

            $note = $message->getNote();

            if (empty($note->getSubways())) {
                $this->logger->debug('There are no subways', [
                    'note_id'          => $note->getId(),
                    'note_external_id' => $note->getExternalId()
                ]);

                return false;
            }

            if (empty($note->getPrice())) {
                $this->logger->debug('There is no price', [
                    'note_id'          => $note->getId(),
                    'note_external_id' => $note->getExternalId()
                ]);

                return false;
            }

            if (count($note->getPhotos()) < 3) {

                $this->logger->debug('There are no photos', [
                    'note_id'          => $note->getId(),
                    'note_external_id' => $note->getExternalId()
                ]);

                return false;
            }

            $record = $this->model_record->findOneByCity((new City())->setShortName($message->getSource()->getCity()));

            if (null === $record) {
                $this->logger->error('There is no publish record for city', [
                    'message_id' => $message->getId(),
                    'city'       => $message->getSource()->getCity()
                ]);

                return false;
            }

            $publisher = $this->publisher_factory->init($record);

            $publisher->publish($note);

            $note
                ->setPublished(true)
                ->setPublishedTimestamp((new \DateTime())->getTimestamp());

            $this->model_note->update($note);

        } catch (\Exception $e) {
            $this->logger->error('Handle error', [
                'message_id' => $message->getId(),
                'error'      => $e->getMessage()
            ]);
        }

        return true;
    }
}