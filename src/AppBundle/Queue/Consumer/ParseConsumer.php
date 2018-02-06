<?php

namespace AppBundle\Queue\Consumer;

use AppBundle\Model\Document\Note\NoteModel;
use AppBundle\Model\Logic\Filter\BlackList\PersonFilter;
use AppBundle\Model\Logic\Filter\BlackList\PhoneFilter;
use AppBundle\Model\Logic\Filter\Expire\DateFilter;
use AppBundle\Model\Logic\Filter\Unique\IdFilter;
use AppBundle\Model\Logic\Parser\ParserFactory;
use AppBundle\Queue\Message\CollectMessage;
use AppBundle\Queue\Message\ParseMessage;
use AppBundle\Queue\Producer\CollectProducer;
use Monolog\Logger;
use Schema\Note\Contact;
use Schema\Note\Note;

class ParseConsumer
{
    private $parser_factory;

    private $filter_expire_date;
    private $filter_unique_id;
    private $filter_black_list_description;
    private $filter_black_list_person;
    private $filter_black_list_phone;
    private $filter_cleaner_description;
    private $filter_replacer_phone;
    private $filter_replacer_vk_id;

    private $producer_collect;

    private $model_note;
    private $logger;

    /**
     * ParseConsumer constructor.
     * @param ParserFactory                                             $parser_factory
     * @param DateFilter                                                $filter_expire_date
     * @param IdFilter                                                  $filter_unique_id
     * @param \AppBundle\Model\Logic\Filter\BlackList\DescriptionFilter $filter_black_list_description
     * @param PersonFilter                                              $filter_black_list_person
     * @param PhoneFilter                                               $filter_black_list_phone
     * @param \AppBundle\Model\Logic\Filter\Cleaner\DescriptionFilter   $filter_cleaner_description
     * @param \AppBundle\Model\Logic\Filter\Replacer\PhoneFilter        $filter_replacer_phone
     * @param \AppBundle\Model\Logic\Filter\Replacer\VkIdFilter         $filter_replacer_vk_id
     * @param CollectProducer                                           $producer_collect
     * @param NoteModel                                                 $model_note
     * @param Logger                                                    $logger
     */
    public function __construct(
        ParserFactory $parser_factory,

        DateFilter $filter_expire_date,
        IdFilter $filter_unique_id,
        \AppBundle\Model\Logic\Filter\BlackList\DescriptionFilter $filter_black_list_description,
        PersonFilter $filter_black_list_person,
        PhoneFilter $filter_black_list_phone,
        \AppBundle\Model\Logic\Filter\Cleaner\DescriptionFilter $filter_cleaner_description,
        \AppBundle\Model\Logic\Filter\Replacer\PhoneFilter $filter_replacer_phone,
        \AppBundle\Model\Logic\Filter\Replacer\VkIdFilter $filter_replacer_vk_id,

        CollectProducer $producer_collect,

        NoteModel $model_note,
        Logger $logger
    )
    {
        $this->parser_factory = $parser_factory;

        $this->filter_expire_date            = $filter_expire_date;
        $this->filter_unique_id              = $filter_unique_id;
        $this->filter_black_list_description = $filter_black_list_description;
        $this->filter_black_list_person      = $filter_black_list_person;
        $this->filter_black_list_phone       = $filter_black_list_phone;
        $this->filter_cleaner_description    = $filter_cleaner_description;
        $this->filter_replacer_phone         = $filter_replacer_phone;
        $this->filter_replacer_vk_id         = $filter_replacer_vk_id;

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

        $parser = $this->parser_factory->init($message->getSource(), $raw->getContent());

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
                ->setCity($city)
                ->setSource($source_type);

            if (!empty($this->filter_unique_id->findDuplicates($note))) {
                $this->logger->debug('Filtered by unique id', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            $description = $this->filter_cleaner_description->clear($parser->description());

            if (!$this->filter_black_list_description->isAllow($description)) {
                $this->logger->debug('Filtered by black list description', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            $contact_id = $parser->contactId();

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

            $contact =
                (new Contact())
                    ->setId($contact_id)
                    ->setName($parser->contactName($contact_id));

            $note->setContact($contact);


            $type = $parser->type();

            if (Note::TYPE_ERR === (int)$type) {
                $this->logger->debug('Filtered by type', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            $note->setType((int)$type);

            $price = $parser->price();

            if (-1 !== $price && 0 !== $price) {
                $note->setPrice($price);
            }

            $contact->setPhones($parser->phones());

            if (!$this->filter_black_list_phone->isAllow($note)) {
                $this->logger->debug('Filtered by black list phone', [
                    'id'   => $id,
                    'city' => $city
                ]);
                unset($note);

                return false;
            }

            foreach ($parser->photos() as $photo) {
                $note->addPhoto($photo);
            }

            foreach ($parser->subways() as $subway) {
                $note->addSubway($subway->getId());
            }

            $description = $this->filter_replacer_phone->replace($description);
            $description = $this->filter_replacer_vk_id->replace($description);
            $note->setDescription($description);

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