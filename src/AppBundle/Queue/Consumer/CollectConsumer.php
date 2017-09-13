<?php

namespace AppBundle\Queue\Consumer;

use AppBundle\Model\Document\Note\NoteModel;
use AppBundle\Model\Logic\Explorer\Subway\SubwayExplorerFactory;
use AppBundle\Model\Logic\Explorer\Tomita\TomitaExplorer;
use AppBundle\Model\Logic\Explorer\User\UserExplorerFactory;
use AppBundle\Model\Logic\Filter\BlackList\PersonFilter;
use AppBundle\Model\Logic\Filter\BlackList\PhoneFilter;
use AppBundle\Model\Logic\Filter\Expire\DateFilter;
use AppBundle\Model\Logic\Filter\Unique\DescriptionFilter;
use AppBundle\Model\Logic\Filter\Unique\ExternalIdFilter;
use AppBundle\Model\Logic\Filter\Unique\NoteFilter;
use AppBundle\Model\Logic\Parser\Contact\ContactParserFactory;
use AppBundle\Model\Logic\Parser\DateTime\DateTimeParserFactory;
use AppBundle\Model\Logic\Parser\Description\DescriptionParserFactory;
use AppBundle\Model\Logic\Parser\Id\IdParserFactory;
use AppBundle\Model\Logic\Parser\Photo\PhotoParserFactory;
use AppBundle\Queue\Message\CollectMessage;
use AppBundle\Queue\Message\PublishMessage;
use AppBundle\Queue\Producer\PublishProducer;
use Monolog\Logger;
use Schema\Note\Contact;
use Schema\Note\Note;

class CollectConsumer
{
    private $producer_publish;

    private $model_note;
    private $logger;

    /**
     * CollectConsumer constructor.
     * @param PublishProducer $producer_publish
     * @param NoteModel       $model_note
     * @param Logger          $logger
     */
    public function __construct(
        PublishProducer $producer_publish,
        NoteModel $model_note,
        Logger $logger
    )
    {
        $this->producer_publish = $producer_publish;
        $this->model_note = $model_note;
        $this->logger = $logger;
    }

    /**
     * @param CollectMessage $message
     * @return bool
     */
    public function handle(CollectMessage $message)
    {
        try {

            $this->logger->debug('Handling message...', [
                'message_id' => $message->getId()
            ]);

            $note = $message->getNote();

            $note->setId($note->getExternalId());

            $this->model_note->create($note);

            $this->producer_publish->publish((
            (new PublishMessage())
                ->setId($message->getId())
                ->setSource($message->getSource())
                ->setNote($note)
            ));

            $this->logger->debug('Handling message... done', [
                'message_id' => $message->getId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Handle error', [
                'message_id' => $message->getId(),
                'error'      => $e->getMessage()
            ]);
        }

        return true;
    }
}