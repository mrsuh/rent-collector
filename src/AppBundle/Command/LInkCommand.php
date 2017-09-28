<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LInkCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:link');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $model_note   = $this->getContainer()->get('model.document.note');
        $model_record = $this->getContainer()->get('model.document.parse.record');

        $source_ids = [];
        foreach ($model_record->findAll() as $record) {
            foreach ($record->getSources() as $source) {
                $source_ids[] = $source->getId();
            }
        }

        foreach ($model_note->findAll() as $note) {

            $link = $note->getLink();
            foreach ($source_ids as $source_id) {
                $link = str_replace($source_id . '-', '', $link);
            }

            $note->setLink($link);
            $model_note->update($note);
        }
    }
}
