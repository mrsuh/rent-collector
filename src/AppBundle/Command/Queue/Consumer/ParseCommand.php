<?php

namespace AppBundle\Command\Queue\Consumer;

use AppBundle\Queue\Producer\ParseProducer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:queue:consumer:parse');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $host   = $this->getContainer()->getParameter('queue.host');
        $port   = $this->getContainer()->getParameter('queue.port');
        $logger = $this->getContainer()->get('monolog.logger.consumer_parse');

        $consumer = $this->getContainer()->get('queue.parse.consumer');

        $queue = new \Pheanstalk\Pheanstalk($host, $port);

        while (true) {

            if (!$queue->getConnection()->isServiceListening()) {

                $logger->error('Queue connect error, wait');

                sleep(1);

                continue;
            }

            $job = $queue
                ->watch(ParseProducer::QUEUE)
                ->reserve();

            $message = unserialize($job->getData());

            try {

                $consumer->handle($message);

                $queue->delete($job);

            } catch (\Exception $e) {
                $logger->error('Queue handle error', ['exception' => $e->getMessage()]);
            }
        }
    }
}
