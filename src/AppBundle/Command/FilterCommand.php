<?php

namespace AppBundle\Command;

use AppBundle\Command\Helper\DisplayTrait;
use AppBundle\Document\Note;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilterCommand extends ContainerAwareCommand
{
    use DisplayTrait;

    protected function configure()
    {
        $this->setName('app:filter');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm_factory = $this->getContainer()->get('odm.hot.data.mapper.factory');
        $dm_note = $dm_factory->init(Note::class);

        $filter_black_list_contacts = $this->getContainer()->get('filter.black_list.contacts');
        $filter_black_list_description = $this->getContainer()->get('filter.black_list.description');

        $filter_unique_external_id = $this->getContainer()->get('filter.unique.external_id');
        $filter_unique_description = $this->getContainer()->get('filter.unique.description');
        $filter_unique             = $this->getContainer()->get('filter.unique');
        $duplicate_ids             = [];

        $count   = 0;

        foreach ($dm_note->find() as $note) {

            if (in_array($note->getId(), $duplicate_ids)) {
                continue;
            }

            if (!$filter_black_list_description->isAllow($note)) {
                $this->debug($note->getId() . ' filter by black list description');
                $count++;
                $dm_note->delete($note);
                unset($note);
                continue;
            }

            if (!$filter_black_list_contacts->isAllow($note)) {
                $this->debug($note->getId() . ' filter by black list contacts');
                $count++;
                $dm_note->delete($note);
                unset($note);
                continue;
            }

            foreach ($filter_unique_description->findDuplicates($note) as $duplicate) {
                $this->debug($duplicate->getId() . ' filter by unique description');
                $duplicate_ids[] = $duplicate->getId();
                $count++;
                $dm_note->delete($duplicate);
            }

            foreach ($filter_unique_external_id->findDuplicates($note) as $duplicate) {
                $this->debug($duplicate->getId() . ' filter by unique external id');
                $duplicate_ids[] = $duplicate->getId();
                $count++;
                $dm_note->delete($duplicate);
            }

            foreach ($duplicates = $filter_unique->findDuplicates($note) as $duplicate) {
                $this->debug($note->getId() . ' filter by unique note');
                $duplicate_ids[] = $duplicate->getId();
                $count++;
                $dm_note->delete($duplicate);
            }
        }

        $this->debug('Total filtered: ' . $count);
    }
}
