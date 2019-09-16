<?php

namespace App\Command\Delete;

use App\Document\Note\NoteModel;
use App\Filter\DuplicateFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeleteByDuplicateCommand extends Command
{
    protected static $defaultName = 'app:delete:by-duplicate';

    private $duplicateFilter;
    private $noteModel;

    public function __construct(DuplicateFilter $duplicateFilter, NoteModel $noteModel)
    {
        $this->duplicateFilter = $duplicateFilter;
        $this->noteModel       = $noteModel;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Finding duplicate notes...');
        $duplicates = [];
        foreach ($this->noteModel->findAllOrderByDate() as $note) {

            if (array_key_exists($note->getId(), $duplicates)) {
                continue;
            }

            $duplicateNotes = $this->duplicateFilter->findContactAndTypeDuplicates(
                $note->getId(),
                $note->getType(),
                $note->getContact()->getId()
            );

            if (empty($duplicateNotes)) {
                continue;
            }

            foreach ($duplicateNotes as $duplicateNote) {
                if (array_key_exists($duplicateNote->getId(), $duplicates)) {
                    continue;
                }

                $duplicates[$duplicateNote->getId()] = $duplicateNote;
            }
        }

        $output->writeln(sprintf('Found %d duplicate notes:', count($duplicates)));

        $helper   = $this->getHelper('question');
        $question = new ConfirmationQuestion('Do you want to delete duplicate notes? ', false);
        if ($helper->ask($input, $output, $question)) {
            foreach ($duplicates as $duplicate) {
                $this->noteModel->delete($duplicate);
                $output->writeln(sprintf('Note %s was deleted', $duplicate->getId()));
            }
        }

        $output->writeln('Done');
    }
}
