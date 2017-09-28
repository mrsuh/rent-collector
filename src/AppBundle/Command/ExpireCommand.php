<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExpireCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:expire');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger      = $this->getContainer()->get('logger');
        $filter_date = $this->getContainer()->get('filter.expire.date');
        $model_note  = $this->getContainer()->get('model.document.note');

        $count = 0;
        foreach ($model_note->findAll() as $note) {
            if (!$filter_date->isExpire($note->getTimestamp())) {
                continue;
            };

            $logger->debug($note->getId() . ' expired');
            $model_note->delete($note);
            $count++;
        }

        $logger->debug('Total expired', [
            'total' => $count
        ]);
    }
}
