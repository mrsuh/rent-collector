<?php

namespace App\Command\Delete;

use App\Document\Note\NoteModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteByExpireDateCommand extends Command
{
    protected static $defaultName = 'app:delete:by-expire-date';

    private $logger;
    private $noteModel;

    public function __construct(LoggerInterface $logger, NoteModel $noteModel)
    {
        $this->logger    = $logger;
        $this->noteModel = $noteModel;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addOption(
                'expire-period',
                null,
                InputOption::VALUE_OPTIONAL,
                '2 weeks',
                '2 weeks'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count           = 0;
        $expireTimestamp = (new \DateTime())->modify(sprintf('- %s', $input->getOption('expire-period')))->getTimestamp();
        foreach ($this->noteModel->findAll() as $note) {
            if ($note->getTimestamp() > $expireTimestamp) {
                continue;
            };

            $this->logger->debug($note->getId() . ' expired');
            $this->noteModel->delete($note);
            $count++;
        }

        $this->logger->debug('Total expired', [
            'total' => $count
        ]);
    }
}
