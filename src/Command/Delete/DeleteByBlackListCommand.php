<?php

namespace App\Command\Delete;

use App\Document\Note\NoteModel;
use App\Filter\BlackListFilter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeleteByBlackListCommand extends Command
{
    protected static $defaultName = 'app:delete:by-black-list';

    private $logger;
    private $noteModel;
    private $blackListFilter;

    public function __construct(
        LoggerInterface $logger,
        NoteModel $noteModel,
        BlackListFilter $blackListFilter
    )
    {
        $this->logger          = $logger;
        $this->noteModel       = $noteModel;
        $this->blackListFilter = $blackListFilter;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $to_delete = [];

        $count = 0;
        foreach ($this->noteModel->findAll() as $note) {

            if (!$this->blackListFilter->isAllow($note->getContact()->getId())) {

                $this->logger->debug($note->getId() . ' will be deleted by person',
                    [
                        'id'     => $note->getId(),
                        'person' => $note->getContact()->getId()
                    ]
                );

                $to_delete[] = $note;
                $count++;
                continue;
            }

            if (!$this->blackListFilter->isAllow($note->getDescription())) {

                $this->logger->debug($note->getId() . ' will be deleted by description',
                    [
                        'id'          => $note->getId(),
                        'description' => $note->getDescription()
                    ]
                );

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
                $this->noteModel->delete($note);
            }
        } else {
            $output->writeln('There are no notes were deleted');

            return;
        }

        $output->writeln(sprintf('Total deleted notes %s', $count));

        $this->logger->debug('Total deleted', [
            'total' => $count
        ]);
    }
}
