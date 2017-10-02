<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BlackListCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:black-list');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger             = $this->getContainer()->get('logger');
        $filter_person      = $this->getContainer()->get('filter.black_list.person');
        $filter_description = $this->getContainer()->get('filter.black_list.description');
        $filter_phone       = $this->getContainer()->get('filter.black_list.phone');

        $model_note = $this->getContainer()->get('model.document.note');

        $count = 0;
        foreach ($model_note->findAll() as $note) {

            if (!$filter_person->isAllow($note->getContact()->getId())) {

                $logger->debug($note->getId() . ' delete by person');

                $model_note->delete($note);
                $count++;
                continue;
            }

            if (!$filter_description->isAllow($note->getDescription())) {

                $logger->debug($note->getId() . ' delete by description');

                $model_note->delete($note);
                $count++;
                continue;
            }

            if (!$filter_phone->isAllow($note)) {

                $logger->debug($note->getId() . ' delete by phone');

                $model_note->delete($note);
                $count++;
                continue;
            }
        }

        $logger->debug('Total deleted', [
            'total' => $count
        ]);
    }
}
