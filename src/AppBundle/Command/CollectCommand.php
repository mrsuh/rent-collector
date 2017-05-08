<?php

namespace AppBundle\Command;

use AppBundle\Command\Helper\DisplayTrait;
use AppBundle\Document\Note;
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
        $container = $this->getContainer();
        $configs   = Yaml::parse(file_get_contents($container->getParameter('file.config.parser')));

        $collector_factory = $this->getContainer()->get('collector.factory');

        $parser_datetime_factory    = $container->get('parser.datetime.factory');
        $parser_description_factory = $container->get('parser.description.factory');
        $parser_photo_factory       = $container->get('parser.photo.factory');
        $parser_contact_factory     = $container->get('parser.contact.factory');

        $filter_expire_date            = $container->get('filter.expire.date');
        $filter_unique_description     = $container->get('filter.unique.description');
        $filter_unique_external_id     = $container->get('filter.unique.external_id');
        $filter_black_list_description = $container->get('filter.black_list.description');
        $filter_black_list_contacts    = $container->get('filter.black_list.contacts');

        $explorer_user_factory = $container->get('explorer.user.factory');
        $explorer_subway       = $container->get('explorer.subway');
        $explorer_tomita       = $container->get('explorer.tomita');

        $dm_note = $container->get('odm.hot.data.mapper.factory')->init(Note::class);

        $count = 0;
        foreach ($configs as $config) {

            $this->debug('[ ' . $config['type'] . ' ][ ' . $config['name'] . ' ]');

            $collector = $collector_factory->init($config['type']);

            $parser_datetime    = $parser_datetime_factory->init($config['type']);
            $parser_description = $parser_description_factory->init($config['type']);
            $parser_photo       = $parser_photo_factory->init($config['type']);
            $parser_contact     = $parser_contact_factory->init($config['type']);

            $explorer_user = $explorer_user_factory->init($config['type']);

            while (!empty($comments = $collector->collect($config))) {

                foreach ($comments as $comment) {

                    try {

                        $this->debug($comment['id'] . ' processing...');

                        $note = (new Note())
                            ->setExternalId($comment['id'])
                            ->setSource($config['type'])
                            ->setCommunity(['name' => $config['name'], 'link' => $config['link']])
                            ->setTimestamp($parser_datetime->parse($comment))
                            ->setCity($config['city']);

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

                        $description = $parser_description->parse($comment);
                        $note->setDescription($description);

                        if (!$filter_black_list_description->isAllow($note)) {
                            $this->debug($note->getExternalId() . ' filter by black list description');
                            unset($note);

                            continue;
                        }

                        $this->debug($note->getExternalId() . ' explore tomita...');
                        $tomita = $explorer_tomita->explore($description);

                        if (Note::ERR === (int)$tomita->getType()) {
                            $this->debug($note->getExternalId() . ' filter by type');
                            unset($note);

                            continue;
                        }

                        $area  = $tomita->getArea();
                        $price = $tomita->getPrice();

                        $note
                            ->setType($tomita->getType())
                            ->setArea(-1 !== $area && 0 !== $area ? $area : null)
                            ->setPrice(-1 !== $price && 0 !== $price ? $price : null);

                        $contact = $parser_contact->parse($comment);

                        $this->debug($note->getExternalId() . ' explore user...');
                        $user = $explorer_user->explore($contact->getId());

                        $note->setContacts([
                            'person' => [
                                'name'  => $user->getFirstName() . ' ' . $user->getLastName(),
                                'photo' => $user->getPhoto(),
                                'link'  => $contact->getLink(),
                                'write' => $contact->getWrite(),
                            ],
                            'phones' => $tomita->getPhones()
                        ]);

                        if (!$filter_black_list_contacts->isAllow($note)) {
                            $this->debug($note->getExternalId() . ' filter by black list contacts');
                            unset($note);

                            continue;
                        }

                        $note->setPhotos($parser_photo->parse($comment));

                        $subways = [];
                        foreach ($explorer_subway->explore($description) as $subway) {
                            $subways[] = $subway->getId();
                        }
                        $note->setSubways($subways);

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
