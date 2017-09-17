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
use AppBundle\Model\Logic\Filter\Unique\IdFilter;
use AppBundle\Model\Logic\Filter\Unique\NoteFilter;
use AppBundle\Model\Logic\Parser\Contact\ContactParserFactory;
use AppBundle\Model\Logic\Parser\DateTime\DateTimeParserFactory;
use AppBundle\Model\Logic\Parser\Description\DescriptionParserFactory;
use AppBundle\Model\Logic\Parser\Id\IdParserFactory;
use AppBundle\Model\Logic\Parser\Link\LinkParserFactory;
use AppBundle\Model\Logic\Parser\Photo\PhotoParserFactory;
use AppBundle\Queue\Message\CollectMessage;
use AppBundle\Queue\Message\ParseMessage;
use AppBundle\Queue\Producer\CollectProducer;
use Monolog\Logger;
use Schema\Note\Contact;
use Schema\Note\Note;

class ParseConsumer
{
    private $parser_datetime_factory;
    private $parser_description_factory;
    private $parser_photo_factory;
    private $parser_contact_factory;
    private $parser_id_factory;
    private $parser_link_factory;

    private $filter_expire_date;
    private $filter_unique_description;
    private $filter_unique_note;
    private $filter_unique_id;
    private $filter_black_list_description;
    private $filter_black_list_person;
    private $filter_black_list_phone;
    private $filter_cleaner_description;

    private $explorer_subway_factory;
    private $explorer_tomita;
    private $explorer_user_factory;

    private $producer_collect;

    private $model_note;
    private $logger;

    public function __construct(
        DateTimeParserFactory $parser_datetime_factory,
        DescriptionParserFactory $parser_description_factory,
        PhotoParserFactory $parser_photo_factory,
        ContactParserFactory $parser_contact_factory,
        IdParserFactory $parser_id_factory,
        LinkParserFactory $parser_link_factory,

        DateFilter $filter_expire_date,
        DescriptionFilter $filter_unique_description,
        NoteFilter $filter_unique_note,
        IdFilter $filter_unique_id,
        \AppBundle\Model\Logic\Filter\BlackList\DescriptionFilter $filter_black_list_description,
        PersonFilter $filter_black_list_person,
        PhoneFilter $filter_black_list_phone,
        \AppBundle\Model\Logic\Filter\Cleaner\DescriptionFilter $filter_cleaner_description,

        SubwayExplorerFactory $explorer_subway_factory,
        TomitaExplorer $explorer_tomita,
        UserExplorerFactory $explorer_user_factory,

        CollectProducer $producer_collect,

        NoteModel $model_note,
        Logger $logger
    )
    {
        $this->parser_datetime_factory    = $parser_datetime_factory;
        $this->parser_description_factory = $parser_description_factory;
        $this->parser_photo_factory       = $parser_photo_factory;
        $this->parser_contact_factory     = $parser_contact_factory;
        $this->parser_id_factory          = $parser_id_factory;
        $this->parser_link_factory        = $parser_link_factory;

        $this->filter_expire_date            = $filter_expire_date;
        $this->filter_unique_description     = $filter_unique_description;
        $this->filter_unique_note            = $filter_unique_note;
        $this->filter_unique_id              = $filter_unique_id;
        $this->filter_black_list_description = $filter_black_list_description;
        $this->filter_black_list_person      = $filter_black_list_person;
        $this->filter_black_list_phone       = $filter_black_list_phone;
        $this->filter_cleaner_description    = $filter_cleaner_description;

        $this->explorer_subway_factory = $explorer_subway_factory;
        $this->explorer_tomita         = $explorer_tomita;
        $this->explorer_user_factory   = $explorer_user_factory;

        $this->producer_collect = $producer_collect;

        $this->model_note = $model_note;

        $this->logger = $logger;
    }

    public function handle(ParseMessage $message)
    {
        try {

            $this->logger->debug('Handling message...', [
                'message_id' => $message->getId(),
                'city'       => $message->getSource()->getCity()
            ]);

            $note = new Note();

            $parser_id = $this->parser_id_factory->init($message->getSource());
            $id        = $parser_id->parse($message->getNote());

            $parser_link = $this->parser_link_factory->init($message->getSource());

            $link = $parser_link->parse($message->getSource(), $id);

            $note->setLink($link);

            if (empty($id)) {

                $this->logger->error('Parsed id is empty', [
                    'message_id' => $message->getId(),
                    'city'       => $message->getSource()->getCity()
                ]);

                return false;
            }

            $external_id = $message->getSource()->getId() . '-' . $id;

            $parser_datetime = $this->parser_datetime_factory->init($message->getSource());
            $timestamp       = $parser_datetime->parse($message->getNote());

            if ($this->filter_expire_date->isExpire($timestamp)) {
                $this->logger->debug('Filtered by expire date', [
                    'external_id' => $external_id,
                    'timestamp'   => $timestamp,
                    'date'        => \DateTime::createFromFormat('U', $timestamp)->format('Y-m-d H:i:s'),
                    'city'        => $message->getSource()->getCity()
                ]);
                unset($note);

                return false;
            }

            $note
                ->setId($external_id)
                ->setExternalId($external_id)
                ->setTimestamp($timestamp)
                ->setCity($message->getSource()->getCity());

            if (!empty($this->filter_unique_id->findDuplicates($note))) {
                $this->logger->debug('Filtered by unique id', [
                    'external_id' => $external_id,
                    'city'        => $message->getSource()->getCity()
                ]);
                unset($note);

                return false;
            }

            $parser_description = $this->parser_description_factory->init($message->getSource());
            $description_raw    = $parser_description->parse($message->getNote());

            $description = $this->filter_cleaner_description->clear($description_raw);

            $note->setDescription($description);

            if (!$this->filter_black_list_description->isAllow($note)) {
                $this->logger->debug('Filtered by black list description', [
                    'external_id' => $external_id,
                    'city'        => $message->getSource()->getCity()
                ]);
                unset($note);

                return false;
            }

            $this->logger->debug('Exploring tomita...', [
                'external_id' => $external_id,
                'city'        => $message->getSource()->getCity()
            ]);

            $time_start = time();

            $tomita = $this->explorer_tomita->explore($description);

            $time_done = time();

            $this->logger->debug('Exploring tomita... done', [
                'external_id'  => $external_id,
                'duration_sec' => $time_done - $time_start,
                'city'         => $message->getSource()->getCity()
            ]);

            if (Note::TYPE_ERR === (int)$tomita->getType()) {
                $this->logger->debug('Filtered by type', [
                    'external_id' => $external_id,
                    'city'        => $message->getSource()->getCity()
                ]);
                unset($note);

                return false;
            }

            $note->setType((int)$tomita->getType());

            $price = $tomita->getPrice();
            if (-1 !== $price && 0 !== $price) {
                $note->setPrice($price);
            }

            $this->logger->debug('Parsing contact...', [
                'message_id'  => $message->getId(),
                'external_id' => $external_id,
                'city'        => $message->getSource()->getCity()
            ]);

            $parser_contact = $this->parser_contact_factory->init($message->getSource());

            $contact_id = $parser_contact->parse($message->getNote());
            $phones     = $tomita->getPhones();

            if (null === $contact_id && count($phones) === 0) {
                $this->logger->debug('Invalid explored user and no phones', [
                    'external_id' => $external_id
                ]);
                unset($note);

                return false;
            }

            $contact = new Contact();
            $contact
                ->setPhones($phones)
                ->setExternalId($contact_id);

            if (null !== $contact_id) {
                $this->logger->debug('Exploring user...', [
                    'external_id' => $external_id,
                    'city'        => $message->getSource()->getCity()
                ]);

                $explorer_user = $this->explorer_user_factory->init($message->getSource());
                $user          = $explorer_user->explore($contact_id);

                $this->logger->debug('Exploring user... done', [
                    'external_id' => $external_id,
                    'city'        => $message->getSource()->getCity()
                ]);

                if ($user->getBlacklisted()) {

                    $this->logger->debug('Filtered by user blacklisted', [
                        'external_id' => $external_id,
                        'city'        => $message->getSource()->getCity()
                    ]);

                    unset($note);

                    return false;
                }

                $contact->setName(null !== $user->getName() ? $user->getName() : 'unknow');
            }

            $note->setContact($contact);

            if (!$this->filter_black_list_person->isAllow($note)) {
                $this->logger->debug('Filtered by black list person', [
                    'external_id' => $external_id,
                    'city'        => $message->getSource()->getCity()
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

            $parser_photo = $this->parser_photo_factory->init($message->getSource());
            foreach ($parser_photo->parse($message->getNote()) as $photo) {
                $note->addPhoto($photo);
            }

            $explorer_subway = $this->explorer_subway_factory->init($message->getSource());
            foreach ($explorer_subway->explore($description) as $subway) {
                $note->addSubway($subway->getId());
            }

            $description_duplicates = $this->filter_unique_description->findDuplicates($note);

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

            $this->logger->debug('Finding unique duplicates...', [
                'message_id'  => $message->getId(),
                'external_id' => $external_id,
                'city'        => $message->getSource()->getCity()
            ]);

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

            if (!$is_duplicate) {
                $this->producer_collect->publish((
                (new CollectMessage())
                    ->setId($message->getId())
                    ->setSource($message->getSource())
                    ->setNote($note)
                ));
            } else {
                $this->logger->debug('Publishing canceled by duplicate', [
                    'message_id' => $message->getId(),
                    'city'       => $message->getSource()->getCity()
                ]);
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
    }
}