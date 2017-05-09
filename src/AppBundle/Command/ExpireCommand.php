<?php

namespace AppBundle\Command;

use AppBundle\Command\Helper\DisplayTrait;
use AppBundle\Document\Note;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExpireCommand extends ContainerAwareCommand
{
    use DisplayTrait;

    protected function configure()
    {
        $this->setName('app:expire');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm_factory_hot = $this->getContainer()->get('odm.hot.data.mapper.factory');
        $dm_note_hot    = $dm_factory_hot->init(Note::class);

        $dm_factory_cold = $this->getContainer()->get('odm.cold.data.mapper.factory');
        $dm_note_cold    = $dm_factory_cold->init(Note::class);

        $filter_date = $this->getContainer()->get('filter.expire.date');

        $notes = $dm_note_hot->find();
        $count = 0;

        foreach ($notes as $note) {
            if ($filter_date->isExpire($note)) {
                $this->debug($note->getId() . ' expired');
                $dm_note_cold->insert($note);
                $dm_note_hot->delete($note);
                $count++;
            };
        }

        $this->debug('Total expired ' . $count);
    }
}
