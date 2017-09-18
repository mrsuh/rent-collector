<?php

namespace AppBundle\Queue\Consumer;

use AppBundle\Model\Document\Note\NoteModel;
use AppBundle\Model\Logic\Filter\Unique\DescriptionFilter;
use AppBundle\Model\Logic\Filter\Unique\IdFilter;
use AppBundle\Model\Logic\Filter\Unique\NoteFilter;
use AppBundle\Queue\Message\CollectMessage;
use AppBundle\Queue\Message\PublishMessage;
use AppBundle\Queue\Producer\PublishProducer;
use Monolog\Logger;

class CollectConsumer
{
    private $producer_publish;

    private $model_note;
    private $logger;

    private $filter_unique_note;
    private $filter_unique_id;
    private $filter_unique_description;

    /**
     * CollectConsumer constructor.
     * @param PublishProducer   $producer_publish
     * @param NoteModel         $model_note
     * @param IdFilter          $filter_unique_id
     * @param NoteFilter        $filter_unique_note
     * @param DescriptionFilter $filter_unique_description
     * @param Logger            $logger
     */
    public function __construct(
        PublishProducer $producer_publish,
        NoteModel $model_note,

        IdFilter $filter_unique_id,
        NoteFilter $filter_unique_note,
        DescriptionFilter $filter_unique_description,

        Logger $logger
    )
    {
        $this->producer_publish = $producer_publish;
        $this->model_note = $model_note;
        $this->logger = $logger;

        $this->filter_unique_note        = $filter_unique_note;
        $this->filter_unique_id          = $filter_unique_id;
        $this->filter_unique_description = $filter_unique_description;
    }

    /**
     * @param CollectMessage $message
     * @return bool
     */
    public function handle(CollectMessage $message)
    {
        try {

            $this->logger->debug('Handling message...', [
                'message_id' => $message->getId(),
                'city'       => $message->getSource()->getCity()
            ]);

            $note = $message->getNote();

            $external_id = $note->getExternalId();

            if (!empty($this->filter_unique_id->findDuplicates($note))) {
                $this->logger->debug('Filtered by unique id', [
                    'external_id' => $external_id,
                    'city'        => $message->getSource()->getCity()
                ]);
                unset($note);

                return false;
            }

            $is_duplicate = false;

            if (!empty($description_duplicates)) {

                $this->logger->debug('Filtered by unique description', [
                    'external_id' => $external_id,
                    'city'        => $message->getSource()->getCity()
                ]);

                foreach ($description_duplicates as $duplicate) {
                    $this->logger->debug('Delete duplicate', [
                        'external_id'  => $external_id,
                        'duplicate_id' => $duplicate->getExternalId(),
                        'city'         => $message->getSource()->getCity()
                    ]);
                    $this->model_note->delete($duplicate);
                    $is_duplicate = true;
                }
            }

            $unique_duplicates = $this->filter_unique_note->findDuplicates($note);

            if (!empty($unique_duplicates)) {
                $this->logger->debug('Filtered by unique', [
                    'external_id' => $external_id,
                    'city'        => $message->getSource()->getCity()
                ]);

                foreach ($unique_duplicates as $duplicate) {
                    $this->logger->debug('Delete duplicate', [
                        'external_id'  => $external_id,
                        'duplicate_id' => $duplicate->getExternalId(),
                        'city'         => $message->getSource()->getCity()
                    ]);

                    $this->model_note->delete($duplicate);
                    $is_duplicate = true;
                }
            }

            $this->model_note->create($note);

            if (!$is_duplicate) {

                $this->logger->debug('Publishing note', [
                    'message_id' => $message->getId(),
                    'city'       => $message->getSource()->getCity()
                ]);

                $this->producer_publish->publish((
                (new PublishMessage())
                    ->setId($message->getId())
                    ->setSource($message->getSource())
                    ->setNote($note)
                ));
            }

            $this->logger->debug('Handling message... done', [
                'message_id' => $message->getId(),
                'city'       => $message->getSource()->getCity()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Handle error', [
                'message_id' => $message->getId(),
                'error'      => $e->getMessage(),
                'city'       => $message->getSource()->getCity()
            ]);
        }

        return true;
    }
}