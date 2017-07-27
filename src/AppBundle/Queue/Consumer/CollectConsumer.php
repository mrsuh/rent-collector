<?php

namespace AppBundle\Queue\Consumer;

use AppBundle\Model\Document\Note\NoteModel;
use AppBundle\Model\Logic\Explorer\Subway\SubwayExplorer;
use AppBundle\Model\Logic\Explorer\Tomita\TomitaExplorer;
use AppBundle\Model\Logic\Explorer\User\UserExplorerFactory;
use AppBundle\Model\Logic\Filter\DateExpireFilter;
use AppBundle\Model\Logic\Filter\DescriptionBlackListFilter;
use AppBundle\Model\Logic\Filter\DescriptionUniqueFilter;
use AppBundle\Model\Logic\Filter\ExternalIdUniqueFilter;
use AppBundle\Model\Logic\Filter\PersonBlackListFilter;
use AppBundle\Model\Logic\Filter\PhoneBlackListFilter;
use AppBundle\Model\Logic\Filter\UniqueFilter;
use AppBundle\Model\Logic\Parser\Contact\ContactParserFactory;
use AppBundle\Model\Logic\Parser\DateTime\DateTimeParserFactory;
use AppBundle\Model\Logic\Parser\Description\DescriptionParserFactory;
use AppBundle\Model\Logic\Parser\Id\IdParserFactory;
use AppBundle\Model\Logic\Parser\Photo\PhotoParserFactory;
use AppBundle\Model\Logic\Publisher\VkPublisher;
use AppBundle\Queue\Message\CollectMessage;
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
    private $filter_unique;
    private $filter_unique_external_id;
    private $filter_black_list_description;
    private $filter_black_list_person;
    private $filter_black_list_phone;

    private $explorer_subway;
    private $explorer_tomita;
    private $explorer_user_factory;

    private $publisher_vk;

    private $model_note;
    private $logger;

    public function __construct(
        DateTimeParserFactory $parser_datetime_factory,
        DescriptionParserFactory $parser_description_factory,
        PhotoParserFactory $parser_photo_factory,
        ContactParserFactory $parser_contact_factory,
        IdParserFactory $parser_id_factory,

        DateExpireFilter $filter_expire_date,
        DescriptionUniqueFilter $filter_unique_description,
        UniqueFilter $filter_unique,
        ExternalIdUniqueFilter $filter_unique_external_id,
        DescriptionBlackListFilter $filter_black_list_description,
        PersonBlackListFilter $filter_black_list_person,
        PhoneBlackListFilter $filter_black_list_phone,

        SubwayExplorer $explorer_subway,
        TomitaExplorer $explorer_tomita,
        UserExplorerFactory $explorer_user_factory,

        VkPublisher $publisher_vk,

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
        $this->filter_unique                 = $filter_unique;
        $this->filter_unique_external_id     = $filter_unique_external_id;
        $this->filter_black_list_description = $filter_black_list_description;
        $this->filter_black_list_person      = $filter_black_list_person;
        $this->filter_black_list_phone       = $filter_black_list_phone;

        $this->explorer_subway       = $explorer_subway;
        $this->explorer_tomita       = $explorer_tomita;
        $this->explorer_user_factory = $explorer_user_factory;

        $this->publisher_vk = $publisher_vk;

        $this->model_note = $model_note;

        $this->logger = $logger;
    }

    public function handle(CollectMessage $message)
    {
        try {

            $this->logger->debug('Handling message...', [
                'message_id' => $message->getId()
            ]);

            $parser_datetime    = $this->parser_datetime_factory->init($message->getSource()->getType());
            $parser_description = $this->parser_description_factory->init($message->getSource()->getType());
            $parser_photo       = $this->parser_photo_factory->init($message->getSource()->getType());
            $parser_contact     = $this->parser_contact_factory->init($message->getSource()->getType());
            $parser_id          = $this->parser_id_factory->init($message->getSource()->getType());

            $explorer_user = $this->explorer_user_factory->init($message->getSource()->getType());


            $this->logger->debug('Parsing id...', [
                'message_id' => $message->getId()
            ]);

            $id = $parser_id->parse($message->getNote());

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

            $timestamp = $parser_datetime->parse($message->getNote());

            $note = (new Note())
                ->setExternalId($external_id)
                ->setTimestamp($timestamp)
                ->setCity($message->getCity());

            if ($this->filter_expire_date->isExpire($note)) {
                $this->logger->debug('Filtered by expire date', [
                    'external_id' => $external_id
                ]);
                unset($note);

                return false;
            }

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

            $description = $parser_description->parse($message->getNote());

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

            $contact = $parser_contact->parse($message->getNote());


            $this->logger->debug('Exploring user...', [
                'external_id' => $external_id
            ]);

            $user = $explorer_user->explore($contact->getExternalId());

            $this->logger->debug('Exploring user... done', [
                'external_id' => $external_id
            ]);

            if (null === $user) {
                $this->logger->debug('Invalid explored user', [
                    'external_id' => $external_id
                ]);
                unset($note);

                return false;
            }

            $note->setContact(
                (new Contact())
                    ->setExternalId($contact->getExternalId())
                    ->setName($user->getFirstName() . ' ' . $user->getLastName())
                    ->setLink($contact->getLink())
                    ->setPhotoLink($user->getPhoto())
                    ->setPhones($tomita->getPhones())
            );

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

            foreach ($parser_photo->parse($message->getNote()) as $photo) {
                $note->addPhoto($photo);
            }

            $this->logger->debug('Exploring subway...', [
                'message_id'  => $message->getId(),
                'external_id' => $external_id
            ]);

            foreach ($this->explorer_subway->explore($description) as $subway) {
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

            $unique_duplicates = $this->filter_unique->findDuplicates($note);

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

            $note->initId();
            $this->model_note->create($note);

            $this->logger->debug('Publishing...', [
                'message_id'  => $message->getId(),
                'external_id' => $external_id
            ]);


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