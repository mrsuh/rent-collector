<?php

namespace App\Queue\Consumer;

use App\Document\Note\NoteModel;
use App\Filter\BlackListFilter;
use App\Filter\DuplicateFilter;
use App\Filter\ReplaceFilter;
use App\Parser\ParserFactory;
use App\Queue\Message\CollectMessage;
use App\Queue\Message\ParseMessage;
use App\Queue\Producer\CollectProducer;
use Psr\Log\LoggerInterface;
use Schema\Note\Contact;
use Schema\Note\Note;

class ParseConsumer
{
    private $parser_factory;
    private $duplicateFilter;
    private $blackListFilter;
    private $replaceFilter;
    private $producer_collect;
    private $model_note;
    private $logger;

    public function __construct(
        ParserFactory $parser_factory,
        DuplicateFilter $duplicateFilter,
        BlackListFilter $blackListFilter,
        ReplaceFilter $replaceFilter,
        CollectProducer $producer_collect,
        NoteModel $model_note,
        LoggerInterface $logger
    )
    {
        $this->parser_factory   = $parser_factory;
        $this->duplicateFilter  = $duplicateFilter;
        $this->blackListFilter  = $blackListFilter;
        $this->replaceFilter    = $replaceFilter;
        $this->producer_collect = $producer_collect;
        $this->model_note       = $model_note;
        $this->logger           = $logger;
    }

    public function handle(ParseMessage $message)
    {
        $raw         = $message->getRaw();
        $id          = $raw->getId();
        $city        = $message->getSource()->getCity();
        $source_type = $message->getSource()->getType();

        $parser = $this->parser_factory->init($message->getSource(), $raw->getContent());

        try {

            $this->logger->debug('Handling message...', [
                'id'   => $id,
                'city' => $city
            ]);

            if (!empty($this->duplicateFilter->findIdDuplicates($id))) {
                $this->logger->debug('Filtered by unique id', [
                    'id'   => $id,
                    'city' => $city
                ]);

                return false;
            }

            $note =
                (new Note())
                    ->setId($id)
                    ->setLink($message->getRaw()->getLink())
                    ->setTimestamp($raw->getTimestamp())
                    ->setCity($city)
                    ->setSource($source_type);

            if (!$this->blackListFilter->isAllow($parser->description())) {
                $this->logger->debug('Filtered by black list description', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            if (empty($parser->contactId())) {
                $this->logger->debug('Empty contact id', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            if (!$this->blackListFilter->isAllow($parser->contactId())) {
                $this->logger->debug('Filtered by black list contact id', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            $note->setContact((new Contact())->setId($parser->contactId()));

            if (Note::TYPE_ERR === $parser->type()) {
                $this->logger->debug('Filtered by type', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            $note->setType($parser->type());

            if ($parser->price() > 0.0) {
                $note->setPrice($parser->price());
            }

            foreach ($parser->photos() as $photo) {
                $note->addPhoto($photo);
            }

            foreach ($parser->subways() as $subway) {
                $note->addSubway($subway->getId());
            }

            $note->setDescription($this->replaceFilter->replace($parser->description()));

            $this->producer_collect->publish((
            (new CollectMessage())
                ->setSource($message->getSource())
                ->setNote($note)
            ));

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