<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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

        $to_delete = [];

        $count = 0;
        foreach ($model_note->findAll() as $note) {

            if (!$filter_person->isAllow($note->getContact()->getId())) {

                $logger->debug($note->getId() . ' will be deleted by person');

                $to_delete[] = $note;
                $count++;
                continue;
            }

            if (!$filter_description->isAllow($note->getDescription())) {

                $logger->debug($note->getId() . ' will be deleted by description');

                $to_delete[] = $note;
                $count++;
                continue;
            }

            if (!$filter_phone->isAllow($note)) {

                $logger->debug($note->getId() . ' will be deleted by phone');

                $to_delete[] = $note;
                $count++;
                continue;
            }
        }

        if (empty($to_delete)) {
            $output->writeln('There are no notes to delete');

            return;
        }

        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion(sprintf('Do you want to delete %s notes?', $count), false);

        if ($helper->ask($input, $output, $question)) {

            foreach ($to_delete as $note) {
                $model_note->delete($note);
            }
        }

        $output->writeln(sprintf('Total deleted notes %s', $count));

        $logger->debug('Total deleted', [
            'total' => $count
        ]);
    }
}
