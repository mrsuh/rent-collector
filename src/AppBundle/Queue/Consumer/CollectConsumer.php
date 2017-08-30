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
    private $parser_datetime_factory;
    private $parser_description_factory;
    private $parser_photo_factory;
    private $parser_contact_factory;
    private $parser_id_factory;

    private $filter_expire_date;
    private $filter_unique_description;
    private $filter_unique_note;
    private $filter_unique_external_id;
    private $filter_black_list_description;
    private $filter_black_list_person;
    private $filter_black_list_phone;
    private $filter_cleaner_description;

    private $explorer_subway_factory;
    private $explorer_tomita;
    private $explorer_user_factory;

    private $producer_publish;

    private $model_note;
    private $logger;

    public function __construct(
        DateTimeParserFactory $parser_datetime_factory,
        DescriptionParserFactory $parser_description_factory,
        PhotoParserFactory $parser_photo_factory,
        ContactParserFactory $parser_contact_factory,
        IdParserFactory $parser_id_factory,

        DateFilter $filter_expire_date,
        DescriptionFilter $filter_unique_description,
        NoteFilter $filter_unique_note,
        ExternalIdFilter $filter_unique_external_id,
        \AppBundle\Model\Logic\Filter\BlackList\DescriptionFilter $filter_black_list_description,
        PersonFilter $filter_black_list_person,
        PhoneFilter $filter_black_list_phone,
        \AppBundle\Model\Logic\Filter\Cleaner\DescriptionFilter $filter_cleaner_description,

        SubwayExplorerFactory $explorer_subway_factory,
        TomitaExplorer $explorer_tomita,
        UserExplorerFactory $explorer_user_factory,

        PublishProducer $producer_publish,

        NoteModel $model_note,
        Logger $logger
    )
    {
        $this->parser_datetime_factory    = $parser_datetime_factory;
        $this->parser_description_factory = $parser_description_factory;
        $this->parser_photo_factory       = $parser_photo_factory;
        $this->parser_contact_factory     = $parser_contact_factory;
        $this->parser_id_factory          = $parser_id_factory;

        $this->filter_expire_date            = $filter_expire_date;
        $this->filter_unique_description     = $filter_unique_description;
        $this->filter_unique_note            = $filter_unique_note;
        $this->filter_unique_external_id     = $filter_unique_external_id;
        $this->filter_black_list_description = $filter_black_list_description;
        $this->filter_black_list_person      = $filter_black_list_person;
        $this->filter_black_list_phone       = $filter_black_list_phone;
        $this->filter_cleaner_description    = $filter_cleaner_description;

        $this->explorer_subway_factory = $explorer_subway_factory;
        $this->explorer_tomita         = $explorer_tomita;
        $this->explorer_user_factory   = $explorer_user_factory;

        $this->producer_publish = $producer_publish;

        $this->model_note = $model_note;

        $this->logger = $logger;
    }

    public function handle(CollectMessage $message)
    {
        try {

            $this->logger->debug('Handling message...', [
                'message_id' => $message->getId()
            ]);

            $note = new Note();

            $this->logger->debug('Parsing id...', [
                'message_id' => $message->getId()
            ]);

            $parser_id = $this->parser_id_factory->init($message->getSource());
            $id        = $parser_id->parse($message->getNote());

            if (empty($id)) {

                $this->logger->error('Parsed id is empty', [
                    'message_id' => $message->getId()
                ]);

                return false;
            }

            $external_id = $message->getSource()->getId() . '-' . $id;

            $this->logger->debug('Parsing timestamp...', [
                'message_id'  => $message->getId(),
                'external_id' => $external_id
            ]);

            $parser_datetime = $this->parser_datetime_factory->init($message->getSource());
            $timestamp       = $parser_datetime->parse($message->getNote());

            $note
                ->setExternalId($external_id)
                ->setTimestamp($timestamp)
                ->setCity($message->getSource()->getCity());

            if ($this->filter_expire_date->isExpire($note)) {
                $this->logger->debug('Filtered by expire date', [
                    'external_id' => $external_id
                ]);
                unset($note);

                return false;
            }

            $this->logger->debug('Filtering by unique external id', [
                'external_id' => $external_id
            ]);

            if (!empty($this->filter_unique_external_id->findDuplicates($note))) {
                $this->logger->debug('Filtered by unique external id', [
                    'external_id' => $external_id
                ]);
                unset($note);

                return false;
            }

            $this->logger->debug('Parsing description...', [
                'message_id'  => $message->getId(),
                'external_id' => $external_id
            ]);

            $parser_description = $this->parser_description_factory->init($message->getSource());
            $description_raw    = $parser_description->parse($message->getNote());

            $description = $this->filter_cleaner_description->clear($description_raw);

            $note->setDescription($description);

            if (!$this->filter_black_list_description->isAllow($note)) {
                $this->logger->debug('Filtered by black list description', [
                    'external_id' => $external_id
                ]);
                unset($note);

                return false;
            }

            $this->logger->debug('Exploring tomita...', [
                'external_id' => $external_id
            ]);

            $tomita = $this->explorer_tomita->explore($description);

            $this->logger->debug('Exploring tomita... done', [
                'external_id' => $external_id
            ]);

            if (Note::TYPE_ERR === (int)$tomita->getType()) {
                $this->logger->debug('Filtered by type', [
                    'external_id' => $external_id
                ]);
                unset($note);

                return false;
            }

            $area  = $tomita->getArea();
            $price = $tomita->getPrice();

            $note->setType((int)$tomita->getType());

            if (-1 !== $area && 0 !== $area) {
                $note->setArea($area);
            }

            if (-1 !== $price && 0 !== $price) {
                $note->setPrice($price);
            }

            $this->logger->debug('Parsing contact...', [
                'message_id'  => $message->getId(),
                'external_id' => $external_id
            ]);

            $parser_contact = $this->parser_contact_factory->init($message->getSource());

            $contact = $parser_contact->parse($message->getNote());
            $phones  = $tomita->getPhones();

            if (null === $contact->getExternalId() && count($phones) === 0) {
                $this->logger->debug('Invalid explored user and no phones', [
                    'external_id' => $external_id
                ]);
                unset($note);

                return false;
            }

            $contact->setPhones($phones);

            if (null !== $contact->getExternalId()) {
                $this->logger->debug('Exploring user...', [
                    'external_id' => $external_id
                ]);

                $explorer_user = $this->explorer_user_factory->init($message->getSource());
                $user          = $explorer_user->explore($contact->getExternalId());

                $this->logger->debug('Exploring user... done', [
                    'external_id' => $external_id
                ]);

                $contact
                    ->setName($user->getFirstName() . ' ' . $user->getLastName())
                    ->setPhotoLink($user->getPhoto());
            }

            $note->setContact($contact);

            if (!$this->filter_black_list_person->isAllow($note)) {
                $this->logger->debug('Filtered by black list person', [
                    'external_id' => $external_id
                ]);
                unset($note);

                return false;
            }

            if (!$this->filter_black_list_phone->isAllow($note)) {
                $this->logger->debug('Filtered by black list phone', [
                    'external_id' => $external_id
                ]);
                unset($note);

                return false;
            }

            $this->logger->debug('Parsing photo...', [
                'message_id'  => $message->getId(),
                'external_id' => $external_id
            ]);

            $parser_photo = $this->parser_photo_factory->init($message->getSource());
            foreach ($parser_photo->parse($message->getNote()) as $photo) {
                $note->addPhoto($photo);
            }

            $this->logger->debug('Exploring subway...', [
                'message_id'  => $message->getId(),
                'external_id' => $external_id
            ]);

            $explorer_subway = $this->explorer_subway_factory->init($message->getSource());
            foreach ($explorer_subway->explore($description) as $subway) {
                $note->addSubway($subway->getId());
            }

            $this->logger->debug('Finding description duplicates...', [
                'message_id'  => $message->getId(),
                'external_id' => $external_id
            ]);

            $description_duplicates = $this->filter_unique_description->findDuplicates($note);

            if (!empty($description_duplicates)) {

                $this->logger->debug('Filtered by unique description', [
                    'external_id' => $external_id
                ]);

                foreach ($description_duplicates as $duplicate) {
                    $this->logger->debug('Replace to cold DB duplicate', [
                        'external_id'  => $external_id,
                        'duplicate_id' => $duplicate->getExternalId()
                    ]);
                    $this->model_note->replaceToColdDB($duplicate);
                }
            }

            $this->logger->debug('Finding unique duplicates...', [
                'message_id'  => $message->getId(),
                'external_id' => $external_id
            ]);

            $unique_duplicates = $this->filter_unique_note->findDuplicates($note);

            if (!empty($unique_duplicates)) {
                $this->logger->debug('Filtered by unique', [
                    'external_id' => $external_id
                ]);

                foreach ($unique_duplicates as $duplicate) {
                    $this->logger->debug('Replace to cold DB duplicate', [
                        'external_id'  => $external_id,
                        'duplicate_id' => $duplicate->getExternalId()
                    ]);

                    $this->model_note->replaceToColdDB($duplicate);
                }
            }

            $this->model_note->create($note);

            $this->logger->debug('Publishing...', [
                'message_id'  => $message->getId(),
                'external_id' => $external_id
            ]);

            $this->producer_publish->publish((
            (new PublishMessage())
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
    }
}