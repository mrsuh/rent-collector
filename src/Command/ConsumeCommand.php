<?php

namespace App\Command;

use App\Queue\Consumer\CollectConsumer;
use App\Queue\Consumer\NotifyConsumer;
use App\Queue\Consumer\ParseConsumer;
use App\Queue\Producer\CollectProducer;
use App\Queue\Producer\NotifyProducer;
use App\Queue\Producer\ParseProducer;
use Pheanstalk\Contract\PheanstalkInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeCommand extends Command
{
    protected static $defaultName = 'app:consume';

    private $logger;
    private $pheanstalk;
    private $collectConsumer;
    private $parseConsumer;
    private $notifyConsumer;

    public function __construct(
        LoggerInterface $logger,
        PheanstalkInterface $pheanstalk,
        CollectConsumer $collectConsumer,
        ParseConsumer $parseConsumer,
        NotifyConsumer $notifyConsumer
    )
    {
        $this->logger          = $logger;
        $this->pheanstalk      = $pheanstalk;
        $this->collectConsumer = $collectConsumer;
        $this->parseConsumer   = $parseConsumer;
        $this->notifyConsumer  = $notifyConsumer;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('channel', null, InputOption::VALUE_REQUIRED, 'collect|parse|notify');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $consumer = null;
        $channel  = null;
        switch ($input->getOption('channel')) {
            case 'collect':
                $consumer = $this->collectConsumer;
                $channel  = CollectProducer::QUEUE;
                break;
            case 'parse':
                $consumer = $this->parseConsumer;
                $channel  = ParseProducer::QUEUE;
                break;
            case 'notify':
                $consumer = $this->notifyConsumer;
                $channel  = NotifyProducer::QUEUE;
                break;
            default:
                $output->writeln('Invalid consumer name');
                exit(1);
        }

        while (true) {

            $job = $this->pheanstalk
                ->watch($channel)
                ->reserve();

            $message = unserialize($job->getData());

            try {

                $consumer->handle($message);

                $this->pheanstalk->delete($job);

            } catch (\Exception $e) {
                $this->logger->error('Queue handle error', ['exception' => $e->getMessage()]);
            }
        }
    }
}
