<?php

namespace App\Queue\Consumer;

use App\Document\Note\NoteModel;
use App\Filter\DuplicateFilter;
use App\Queue\Message\CollectMessage;
use App\Queue\Message\NotifyMessage;
use App\Queue\Producer\NotifyProducer;
use Psr\Log\LoggerInterface;

class CollectConsumer
{
    private $model_note;
    private $logger;
    private $duplicateFilter;
    private $producer_notify;
    private $notify_duplicate_period;

    public function __construct(
        NoteModel $model_note,
        DuplicateFilter $duplicateFilter,
        NotifyProducer $producer_notify,
        LoggerInterface $logger,
        string $notifierDuplicatePeriod
    )
    {
        $this->model_note              = $model_note;
        $this->logger                  = $logger;
        $this->duplicateFilter         = $duplicateFilter;
        $this->producer_notify         = $producer_notify;
        $this->notify_duplicate_period = $notifierDuplicatePeriod;
    }

    /**
     * @param CollectMessage $message
     * @return bool
     */
    public function handle(CollectMessage $message)
    {
        $note = $message->getNote();

        $id   = $note->getId();
        $city = $message->getSource()->getCity();

        try {

            $this->logger->debug('Handling message...', [
                'id'   => $id,
                'city' => $city
            ]);

            $note = $message->getNote();

            if (!empty($this->duplicateFilter->findIdDuplicates($note->getId()))) {
                $this->logger->debug('Filtered by unique id', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            $is_duplicate           = false;
            $duplicate_timestamp    = 0;
            $description_duplicates = $this->duplicateFilter->findDescriptionDuplicates($note->getId(), $note->getDescriptionHash());
            foreach ($description_duplicates as $duplicate) {

                $this->logger->debug('Delete duplicate by unique description', [
                    'id'               => $id,
                    'city'             => $city,
                    'duplicate_id'     => $duplicate->getId(),
                    'description'      => $note->getDescription(),
                    'description_hash' => $note->getDescriptionHash()
                ]);

                if ($duplicate->getTimestamp() > $duplicate_timestamp) {
                    $duplicate_timestamp = $duplicate->getTimestamp();
                }

                $this->model_note->delete($duplicate);

                $is_duplicate = true;
            }

            $unique_duplicates = $this->duplicateFilter->findContactAndTypeDuplicates($note->getId(), $note->getType(), $note->getContact()->getId());
            foreach ($unique_duplicates as $duplicate) {
                $this->logger->debug('Delete duplicate by unique', [
                    'id'           => $id,
                    'city'         => $city,
                    'duplicate_id' => $duplicate->getId()
                ]);

                if ($duplicate->getTimestamp() > $duplicate_timestamp) {
                    $duplicate_timestamp = $duplicate->getTimestamp();
                }

                $this->model_note->delete($duplicate);

                $is_duplicate = true;
            }

            $note->setDuplicated($is_duplicate);

            $this->model_note->create($note);

            $notify_allow_timestamp = (new \DateTime())->modify('- ' . $this->notify_duplicate_period)->getTimestamp();
            if (!$is_duplicate || $duplicate_timestamp < $notify_allow_timestamp) {

                $this->logger->debug('Notify note', [
                    'id'   => $id,
                    'city' => $city
                ]);

                $this->producer_notify->publish(
                    (new NotifyMessage())
                        ->setNote($note)
                );
            } else {

                $this->logger->debug('Notify canceled by duplicate', [
                    'id'   => $id,
                    'city' => $city
                ]);
            }

            $this->logger->debug('Handling message... done', [
                'id'   => $id,
                'city' => $city
            ]);

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