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
    private $model_note;
    private $logger;

    private $filter_unique_note;
    private $filter_unique_id;
    private $filter_unique_description;

    /**
     * CollectConsumer constructor.
     * @param NoteModel         $model_note
     * @param IdFilter          $filter_unique_id
     * @param NoteFilter        $filter_unique_note
     * @param DescriptionFilter $filter_unique_description
     * @param Logger            $logger
     */
    public function __construct(
        NoteModel $model_note,

        IdFilter $filter_unique_id,
        NoteFilter $filter_unique_note,
        DescriptionFilter $filter_unique_description,

        Logger $logger
    )
    {
        $this->model_note       = $model_note;
        $this->logger           = $logger;

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
        $note = $message->getNote();

        $id   = $note->getId();
        $city = $message->getSource()->getCity();

        try {

            $this->logger->debug('Handling message...', [
                'id'   => $id,
                'city' => $city
            ]);

            $note = $message->getNote();

            if (!empty($this->filter_unique_id->findDuplicates($note))) {
                $this->logger->debug('Filtered by unique id', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            $description_duplicates = $this->filter_unique_description->findDuplicates($note);

            foreach ($description_duplicates as $duplicate) {
                $this->logger->debug('Delete duplicate by unique description', [
                    'id'           => $id,
                    'city'         => $city,
                    'duplicate_id' => $duplicate->getId()
                ]);
                $this->model_note->delete($duplicate);
            }

            $unique_duplicates = $this->filter_unique_note->findDuplicates($note);

            foreach ($unique_duplicates as $duplicate) {
                $this->logger->debug('Delete duplicate by unique', [
                    'id'           => $id,
                    'city'         => $city,
                    'duplicate_id' => $duplicate->getId()
                ]);

                $this->model_note->delete($duplicate);
            }

            $this->model_note->create($note);

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