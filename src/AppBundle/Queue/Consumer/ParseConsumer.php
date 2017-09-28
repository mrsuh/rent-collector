<?php

namespace AppBundle\Queue\Consumer;

use AppBundle\Model\Document\Note\NoteModel;
use AppBundle\Model\Logic\Filter\BlackList\PersonFilter;
use AppBundle\Model\Logic\Filter\BlackList\PhoneFilter;
use AppBundle\Model\Logic\Filter\Expire\DateFilter;
use AppBundle\Model\Logic\Filter\Unique\DescriptionFilter;
use AppBundle\Model\Logic\Filter\Unique\IdFilter;
use AppBundle\Model\Logic\Filter\Unique\NoteFilter;
use AppBundle\Model\Logic\Parser\ContactId\ContactIdParserFactory;
use AppBundle\Model\Logic\Parser\ContactName\ContactNameParserFactory;
use AppBundle\Model\Logic\Parser\Description\DescriptionParserFactory;
use AppBundle\Model\Logic\Parser\Phone\PhoneParserFactory;
use AppBundle\Model\Logic\Parser\Photo\PhotoParserFactory;
use AppBundle\Model\Logic\Parser\Price\PriceParserFactory;
use AppBundle\Model\Logic\Parser\Subway\SubwayParserFactory;
use AppBundle\Model\Logic\Parser\Type\TypeParserFactory;
use AppBundle\Queue\Message\CollectMessage;
use AppBundle\Queue\Message\ParseMessage;
use AppBundle\Queue\Producer\CollectProducer;
use Monolog\Logger;
use Schema\Note\Contact;
use Schema\Note\Note;
use Schema\Parse\Record\Source;

class ParseConsumer
{
    private $parser_description_factory;
    private $parser_photo_factory;
    private $parser_contact_name_factory;
    private $parser_contact_id_factory;
    private $parser_type_factory;
    private $parser_price_factory;
    private $parser_subway_factory;
    private $parser_phone_factory;

    private $filter_expire_date;
    private $filter_unique_description;
    private $filter_unique_note;
    private $filter_unique_id;
    private $filter_black_list_description;
    private $filter_black_list_person;
    private $filter_black_list_phone;
    private $filter_cleaner_description;

    private $producer_collect;

    private $model_note;
    private $logger;

    public function __construct(
        DescriptionParserFactory $parser_description_factory,
        PhotoParserFactory $parser_photo_factory,
        ContactNameParserFactory $parser_contact_name_factory,
        ContactIdParserFactory $parser_contact_id_factory,
        TypeParserFactory $parser_type_factory,
        PriceParserFactory $parser_price_factory,
        PhoneParserFactory $parser_phone_factory,
        SubwayParserFactory $parser_subway_factory,

        DateFilter $filter_expire_date,
        DescriptionFilter $filter_unique_description,
        NoteFilter $filter_unique_note,
        IdFilter $filter_unique_id,
        \AppBundle\Model\Logic\Filter\BlackList\DescriptionFilter $filter_black_list_description,
        PersonFilter $filter_black_list_person,
        PhoneFilter $filter_black_list_phone,
        \AppBundle\Model\Logic\Filter\Cleaner\DescriptionFilter $filter_cleaner_description,

        CollectProducer $producer_collect,

        NoteModel $model_note,
        Logger $logger
    )
    {
        $this->parser_description_factory  = $parser_description_factory;
        $this->parser_photo_factory        = $parser_photo_factory;
        $this->parser_contact_name_factory = $parser_contact_name_factory;
        $this->parser_contact_id_factory   = $parser_contact_id_factory;
        $this->parser_type_factory         = $parser_type_factory;
        $this->parser_price_factory        = $parser_price_factory;
        $this->parser_phone_factory        = $parser_phone_factory;
        $this->parser_subway_factory       = $parser_subway_factory;

        $this->filter_expire_date            = $filter_expire_date;
        $this->filter_unique_description     = $filter_unique_description;
        $this->filter_unique_note            = $filter_unique_note;
        $this->filter_unique_id              = $filter_unique_id;
        $this->filter_black_list_description = $filter_black_list_description;
        $this->filter_black_list_person      = $filter_black_list_person;
        $this->filter_black_list_phone       = $filter_black_list_phone;
        $this->filter_cleaner_description    = $filter_cleaner_description;

        $this->producer_collect = $producer_collect;

        $this->model_note = $model_note;

        $this->logger = $logger;
    }

    public function handle(ParseMessage $message)
    {
        $raw         = $message->getRaw();
        $id          = $raw->getId();
        $city        = $message->getSource()->getCity();
        $source_type = $message->getSource()->getType();

        try {

            $this->logger->debug('Handling message...', [
                'id'   => $id,
                'city' => $city
            ]);

            $timestamp = $raw->getTimestamp();

            if ($this->filter_expire_date->isExpire($timestamp)) {
                $this->logger->debug('Filtered by expire date', [
                    'id'        => $id,
                    'city'      => $city,
                    'timestamp' => $timestamp,
                    'date'      => \DateTime::createFromFormat('U', $timestamp)->format('Y-m-d H:i:s')
                ]);

                return false;
            }

            $note = new Note();

            $note
                ->setId($id)
                ->setLink($message->getRaw()->getLink())
                ->setTimestamp($timestamp)
                ->setCity($city);

            if (!empty($this->filter_unique_id->findDuplicates($note))) {
                $this->logger->debug('Filtered by unique id', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            /**
             *  Description
             */
            $parser_description = $this->parser_description_factory->init($source_type);
            $description_raw    = $parser_description->parse($raw->getContent());

            $description = $this->filter_cleaner_description->clear($description_raw);

            if (!$this->filter_black_list_description->isAllow($description)) {
                $this->logger->debug('Filtered by black list description', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            $note->setDescription($description);

            $is_duplicate           = false;
            $description_duplicates = $this->filter_unique_description->findDuplicates($note);
            foreach ($description_duplicates as $duplicate) {
                $this->logger->debug('Delete duplicate by unique description', [
                    'id'           => $id,
                    'city'         => $city,
                    'duplicate_id' => $duplicate->getId()
                ]);
                $this->model_note->delete($duplicate);
                $is_duplicate = true;
            }

            /**
             * Contact id
             */
            $parser_contact_id = $this->parser_contact_id_factory->init($source_type);
            $contact_id        = $parser_contact_id->parse($raw->getContent());

            if (empty($contact_id)) {
                $this->logger->debug('Empty contact id', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            if (!$this->filter_black_list_person->isAllow($contact_id)) {
                $this->logger->debug('Filtered by black list contact id', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            /**
             * Contact name
             */
            $parser_contact_name = $this->parser_contact_name_factory->init($source_type);

            if (Source::TYPE_AVITO === $source_type) {
                $contact_name = $parser_contact_name->parse($raw->getContent());
            } else {
                $contact_name = $parser_contact_name->parse($contact_id);
            }

            $contact =
                (new Contact())
                    ->setId($contact_id)
                    ->setName($contact_name);

            $note->setContact($contact);

            /**
             *  Type
             */
            $parser_type = $this->parser_type_factory->init($source_type);
            $type        = $parser_type->parse($raw->getContent());

            if (Note::TYPE_ERR === (int)$type) {
                $this->logger->debug('Filtered by type', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            $note->setType((int)$type);

            /**
             *  Price
             */
            $parser_price = $this->parser_price_factory->init($source_type);
            $price        = $parser_price->parse($raw->getContent());

            if (-1 !== $price && 0 !== $price) {
                $note->setPrice($price);
            }

            /**
             * Phone
             */
            $parser_phone = $this->parser_phone_factory->init($source_type);
            $phones       = $parser_phone->parse($raw->getContent());

            $contact->setPhones($phones);

            if (!$this->filter_black_list_phone->isAllow($note)) {
                $this->logger->debug('Filtered by black list phone', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            /**
             * Photo
             */
            $parser_photo = $this->parser_photo_factory->init($source_type);
            foreach ($parser_photo->parse($raw->getContent()) as $photo) {
                $note->addPhoto($photo);
            }

            /**
             * Subway
             */
            $parser_subway = $this->parser_subway_factory->init($source_type);
            foreach ($parser_subway->parse($raw->getContent(), $city) as $subway) {
                $note->addSubway($subway->getId());
            }

            $unique_duplicates = $this->filter_unique_note->findDuplicates($note);
            foreach ($unique_duplicates as $duplicate) {
                $this->logger->debug('Delete duplicate by unique', [
                    'id'           => $id,
                    'city'         => $city,
                    'duplicate_id' => $duplicate->getId()
                ]);

                $this->model_note->delete($duplicate);
                $is_duplicate = true;
            }

            if (!$is_duplicate) {
                $this->producer_collect->publish((
                (new CollectMessage())
                    ->setSource($message->getSource())
                    ->setNote($note)
                ));
            } else {
                $this->logger->debug('Publishing canceled by duplicate', [
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
    }
}