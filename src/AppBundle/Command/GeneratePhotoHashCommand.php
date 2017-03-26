<?php

namespace AppBundle\Command;

use AppBundle\ODM\Document\Note;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratePhotoHashCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:photo:hash:generate');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm_factory = $this->getContainer()->get('odm.hot.data.mapper.factory');
        $dm_note    = $dm_factory->init(Note::class);
        $count      = 0;
        foreach ($dm_note->find() as $note) {
            if (empty($note->getPhotos())) {
                continue;
            }

            if (!empty($note->getPhotoHashes())) {
                continue;
            }

            $output->writeln($note->getId() . ' generating...');
            $note->initPhotoHashes();
            $count++;

            $dm_note->update($note);
        }

        $output->writeln('Total generated: ' . $count);
    }
}
