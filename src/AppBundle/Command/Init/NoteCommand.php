<?php

namespace AppBundle\Command\Init;

use AppBundle\ODM\Document\Note;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NoteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:init:note');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm_factory = $this->getContainer()->get('odm.data.mapper.factory');
        $dm_note    = $dm_factory->init(Note::class);
        $dm_note->drop();
    }
}
