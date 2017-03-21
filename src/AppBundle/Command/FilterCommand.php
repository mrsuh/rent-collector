<?php

namespace AppBundle\Command;

use AppBundle\ODM\Document\Note;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilterCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:filter');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm_factory = $this->getContainer()->get('odm.data.mapper.factory');
        $dm_note    = $dm_factory->init(Note::class);

        $filter_date = $this->getContainer()->get('filter.pre.date');

        $notes = $dm_note->find(['active' => true, 'deleted' => false]);

        foreach ($notes as $note) {
            if (!$filter_date->check($note)) {
                $note->setActive(false);
                $note->setDeleted(true);
                $dm_note->update($note);
            };
        }
    }
}
