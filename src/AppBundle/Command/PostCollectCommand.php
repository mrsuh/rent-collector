<?php

namespace AppBundle\Command;

use AppBundle\ODM\Document\Note;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PostCollectCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:collect:post');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm_factory = $this->getContainer()->get('odm.data.mapper.factory');
        $dm_note    = $dm_factory->init(Note::class);

        $filter_unique = $this->getContainer()->get('filter.post.unique');
        $filter_active = $this->getContainer()->get('filter.post.active');

        foreach ($dm_note->find(['active' => false, 'deleted' => false]) as $note) {
            $filter_unique->filter($note);
        }

        foreach ($dm_note->find(['active' => false, 'deleted' => false]) as $note) {
            $filter_active->activate($note);
        }
    }
}
