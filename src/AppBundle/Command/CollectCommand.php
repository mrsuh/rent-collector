<?php

namespace AppBundle\Command;

use AppBundle\Queue\Message\ParseMessage;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CollectCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:collect');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $collector_factory = $this->getContainer()->get('collector.factory');

        $model_parser = $this->getContainer()->get('model.document.parse.record');
        $logger       = $this->getContainer()->get('logger');
        $producer     = $this->getContainer()->get('queue.parse.producer');

        $count = 0;
        foreach ($model_parser->findAll() as $record) {

            $logger->debug('Collect record', [
                'record' => $record->getName(),
                'city'   => $record->getCity(),
            ]);

            foreach ($record->getSources() as $source) {

                $logger->debug('Collect source', [
                    'type'       => $source->getType(),
                    'parameters' => $source->getParameters()
                ]);

                $collector = $collector_factory->init($source);

                while (!empty($notes = $collector->collect($source))) {

                    $logger->debug('Collect request done', [
                        'notes' => count($notes)
                    ]);

                    foreach ($notes as $note) {
                        $count++;
                        $message =
                            (new ParseMessage())
                                ->setSource($source)
                                ->setNote($note);

                        $producer->publish($message);
                    }
                }
            }
        }

        $logger->debug('Total notes', [
            'total' => $count
        ]);
    }
}
