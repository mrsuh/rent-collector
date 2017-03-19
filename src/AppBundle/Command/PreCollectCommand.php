<?php

namespace AppBundle\Command;

use AppBundle\ODM\Document\Log;
use AppBundle\ODM\Document\Note;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class PreCollectCommand extends ContainerAwareCommand
{
    private $_debug;

    protected function configure()
    {
        $this->setName('app:collect:pre');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_debug = 'dev' === $this->getContainer()->getParameter('kernel.environment');

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

        $filter_date   = $this->getContainer()->get('filter.pre.date');
        $filter_unique = $this->getContainer()->get('filter.pre.unique');

        $black_list_description = $this->getContainer()->get('filter.black_list.description');
        $black_list_person      = $this->getContainer()->get('filter.black_list.person');

        $explorer_contact_factory = $this->getContainer()->get('explorer.contact.factory');

        $dm_note = $this->getContainer()->get('odm.data.mapper.factory')->init(Note::class);

        foreach ($configs as $config) {

            $this->debug('NEW CONFIG ================================================');

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

            while (!empty($comments = $collector->collect($config, $this->_debug))) {

                $this->debug('NEW BALK ================================================');

                foreach ($comments as $comment) {

                    try {

                        $this->debug($comment['id'] . ' ' . json_encode($config['data']));

                        $timestamp = $parser_datetime->parse($comment);

                        $note = (new Note())
                            ->setExternalId($comment['id'])
                            ->setSource($config['type'])
                            ->setCommunity(['name' => $config['name'], 'link' => $config['link']])
                            ->setTimestamp($timestamp);

                        if (!$filter_date->check($note)) {
                            $this->debug('filter by date ' . $timestamp);
                            unset($note);
                            continue;
                        }

                        if (!$filter_unique->check($note)) {
                            $this->debug('filter by unique ' . $note->getExternalId());
                            unset($note);
                            continue;
                        }

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

                        $note
                            ->setContacts($contact)
                            ->setDescription($parser_description->parse($comment));

                        if (!$black_list_description->isAllow($note)) {
                            $this->debug('filter by black list description ' . $note->getExternalId());
                            unset($note);
                            continue;
                        }

                        if (!$black_list_person->isAllow($note)) {
                            $this->debug('filter by black list person ' . $note->getExternalId());
                            unset($note);
                            continue;
                        }

                        $type = $parser_type->parse($comment);

                        if (6 === (int)$type) {
                            $this->debug('filter by type ' . $type);
                            continue;
                        }

                        $subways = [];
                        foreach ($parser_subway->parse($comment) as $subway) {
                            $subways[] = $subway->getId();
                        }

                        $note
                            ->setArea($parser_area->parse($comment))
                            ->setPrice($parser_price->parse($comment))
                            ->setSubways($subways)
                            ->setPhotos($parser_photo->parse($comment))
                            ->setTimestamp($parser_datetime->parse($comment))
                            ->setType($parser_type->parse($comment))
                            ->setCity($config['city']);

                        $notes[] = $note;

                        $dm_note->insert($note);

                    } catch (\Exception $e) {
                        $this->debug($e->getMessage());
                        $this->debug(json_encode($comment));
                    }
                }
            }
        }
    }

    /**
     * @param $message
     */
    private function debug($message)
    {
        if ($this->_debug) {
            echo $message . PHP_EOL;
        }
    }
}
