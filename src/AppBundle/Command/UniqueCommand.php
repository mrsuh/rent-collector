<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class UniqueCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:unique');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Finding duplicate notes...');

        $filter_unique = $this->getContainer()->get('filter.unique.note');

        $model_note = $this->getContainer()->get('model.document.note');

        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion('Do you want to delete duplicate notes? ', false);
        foreach ($model_note->findAllOrderByDate() as $note) {

            if (null === $model_note->findOneById($note->getId())) {

                continue;
            }

            $duplicates = $filter_unique->findDuplicates($note);

            if (empty($duplicates)) {

                continue;
            }

            $output->writeln('Duplicate notes:');
            foreach ($duplicates as $duplicate) {
                $output->writeln(sprintf('Contact: %s:%s, Description: %s', $duplicate->getContact()->getId(), $note->getContact()->getName(), $note->getDescription()));
            }

            if ($helper->ask($input, $output, $question)) {
                foreach ($duplicates as $duplicate) {
                    $model_note->delete($duplicate);
                    $output->writeln(sprintf('Note %s was deleted', $duplicate->getId()));
                }
            }
        }

        $output->writeln('Done');
    }
}
