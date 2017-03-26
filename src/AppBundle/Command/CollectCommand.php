<?php

namespace AppBundle\Command;

use AppBundle\Command\Helper\DisplayTrait;
use AppBundle\ODM\Document\Note;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class CollectCommand extends ContainerAwareCommand
{
    use DisplayTrait;

    protected function configure()
    {
        $this->setName('app:collect');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configs = Yaml::parse(file_get_contents($this->getContainer()->getParameter('file.config.parser')));

        $collector_factory = $this->getContainer()->get('collector.factory');

        $parser_area_factory        = $this->getContainer()->get('parser.area.factory');
        $parser_contact_factory     = $this->getContainer()->get('parser.contact.factory');
        $parser_datetime_factory    = $this->getContainer()->get('parser.datetime.factory');
        $parser_description_factory = $this->getContainer()->get('parser.description.factory');
        $parser_photo_factory       = $this->getContainer()->get('parser.photo.factory');
        $parser_price_factory       = $this->getContainer()->get('parser.price.factory');
        $parser_subway_factory      = $this->getContainer()->get('parser.subway.factory');
        $parser_type_factory        = $this->getContainer()->get('parser.type.factory');

        $filter_expire_date = $this->getContainer()->get('filter.expire.date');

        $filter_unique_description = $this->getContainer()->get('filter.unique.description');
        $filter_unique_external_id = $this->getContainer()->get('filter.unique.external_id');

        $black_list_description = $this->getContainer()->get('filter.black_list.description');
        $black_list_contacts = $this->getContainer()->get('filter.black_list.contacts');

        $explorer_contact_factory = $this->getContainer()->get('explorer.contact.factory');

        $dm_note = $this->getContainer()->get('odm.hot.data.mapper.factory')->init(Note::class);

        $count = 0;
        foreach ($configs as $config) {

            $this->debug('[ ' . $config['type'] . ' ][ ' . $config['name'] . ' ]');

            $collector = $collector_factory->init($config['type']);

            $parser_area        = $parser_area_factory->init($config['type']);
            $parser_contact     = $parser_contact_factory->init($config['type']);
            $parser_datetime    = $parser_datetime_factory->init($config['type']);
            $parser_description = $parser_description_factory->init($config['type']);
            $parser_photo       = $parser_photo_factory->init($config['type']);
            $parser_price       = $parser_price_factory->init($config['type']);
            $parser_subway      = $parser_subway_factory->init($config['type']);
            $parser_type        = $parser_type_factory->init($config['type']);

            $explorer_contact = $explorer_contact_factory->init($config['type']);

            $notes = [];

            while (!empty($comments = $collector->collect($config))) {

                foreach ($comments as $comment) {

                    try {

                        $this->debug($comment['id'] . ' processing...');

                        $timestamp = $parser_datetime->parse($comment);

                        $note = (new Note())
                            ->setExternalId($comment['id'])
                            ->setSource($config['type'])
                            ->setCommunity(['name' => $config['name'], 'link' => $config['link']])
                            ->setTimestamp($timestamp);

                        if ($filter_expire_date->isExpire($note)) {
                            $this->debug($note->getExternalId() . ' filter by expire date');
                            unset($note);
                            continue;
                        }

                        if (!empty($filter_unique_external_id->findDuplicates($note))) {
                            $this->debug($note->getExternalId() . ' filter by unique external id');
                            unset($note);
                            continue;
                        }

                        $note->setDescription($parser_description->parse($comment));
                        $note->initDescriptionHash();

                        if (!$black_list_description->isAllow($note)) {
                            $this->debug($note->getExternalId() . ' filter by black list description');
                            unset($note);
                            continue;
                        }

                        $note->setPhotos($parser_photo->parse($comment));

                        $this->debug($note->getExternalId() . ' parse contacts...');
                        $contact_parse   = $parser_contact->parse($comment);
                        $contact_explore = $explorer_contact->explore($comment);

                        $contact = [
                            'person' => [
                                'name'  => $contact_explore,
                                'link'  => $contact_parse['link'],
                                'write' => $contact_parse['write'],
                            ],
                            'phones' => $contact_parse['phones']
                        ];

                        $note->setContacts($contact);

                        if (!$black_list_contacts->isAllow($note)) {
                            $this->debug($note->getExternalId() . ' filter by black list contacts');
                            unset($note);
                            continue;
                        }

                        $type = $parser_type->parse($comment);

                        if (6 === (int)$type) {
                            $this->debug($note->getExternalId() . ' filter by type');
                            unset($note);
                            continue;
                        }

                        $this->debug($note->getExternalId() . ' parse subways...');
                        $subways = [];
                        foreach ($parser_subway->parse($comment) as $subway) {
                            $subways[] = $subway->getId();
                        }

                        $this->debug($note->getExternalId() . ' parse area...');
                        $note->setArea($parser_area->parse($comment));

                        $this->debug($note->getExternalId() . ' parse price...');
                        $note->setPrice($parser_price->parse($comment));

                        $this->debug($note->getExternalId() . ' parse type...');
                        $note->setType($parser_type->parse($comment));

                        $note
                            ->setSubways($subways)
                            ->setTimestamp($parser_datetime->parse($comment))
                            ->setCity($config['city']);

                        $notes[] = $note;

                        if (!empty($filter_unique_description->findDuplicates($note))) {
                            $this->debug($note->getExternalId() . ' filter by unique description');
                            foreach ($dm_note->find(['description_hash' => $note->getDescriptionHash()]) as $duplicate) {
                                $this->debug($note->getExternalId() . ' delete duplicate');
                                $dm_note->delete($duplicate);
                            }
                        }

                        $note->initId();
                        $dm_note->insert($note);
                        $count++;

                    } catch (\Exception $e) {
                        $this->debug($e->getMessage());
                        $this->debug(json_encode($comment));
                    }
                }
            }
        }

        $this->debug('Total collected: ' . $count);
    }
}
