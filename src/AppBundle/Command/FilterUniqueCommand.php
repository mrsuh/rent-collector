<?php

namespace AppBundle\Command;

use AppBundle\ODM\Document\Note;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilterUniqueCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:filter:unique');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm_factory = $this->getContainer()->get('odm.data.mapper.factory');
        $dm_note    = $dm_factory->init(Note::class);

        $filter_unique = $this->getContainer()->get('filter.post.unique');

        foreach ($dm_note->find(['active' => true, 'expired' => false]) as $note) {
            $filter_unique->filter($note);
        }
    }
}
