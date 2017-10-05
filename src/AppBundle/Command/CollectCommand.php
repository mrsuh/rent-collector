<?php

namespace AppBundle\Command;

use AppBundle\Queue\Message\ParseMessage;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CollectCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:collect')
            ->addOption(
                'city',
                null,
                InputOption::VALUE_OPTIONAL,
                null
            )->addOption(
                'record',
                null,
                InputOption::VALUE_OPTIONAL,
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $collector_factory = $this->getContainer()->get('collector.factory');

        $model_parser = $this->getContainer()->get('model.document.parse.record');
        $logger       = $this->getContainer()->get('logger');
        $producer     = $this->getContainer()->get('queue.parse.producer');

        $city    = $input->getOption('city');
        $records = empty($city) ? $model_parser->findAll() : $model_parser->findByCity($city);

        $record_id = $input->getOption('record');

        if (empty($records)) {
            $logger->error('There are no records');

            return false;
        }

        $count = 0;
        foreach ($records as $record) {

            $logger->debug('Collect record', [
                'record' => $record->getName(),
                'city'   => $record->getCity(),
            ]);

            if (!empty($record_id) && $record_id !== $record->getId()) {

                continue;
            }

            foreach ($record->getSources() as $source) {

                $logger->debug('Collect source', [
                    'type'       => $source->getType(),
                    'parameters' => $source->getParameters()
                ]);

                $collector = $collector_factory->init($source);

                while (!empty($raws = $collector->collect($source))) {

                    $logger->debug('Collect request done', [
                        'notes' => count($raws)
                    ]);

                    foreach ($raws as $raw) {
                        $count++;

                        $raw = $collector->handle($raw);

                        $message =
                            (new ParseMessage())
                                ->setSource($source)
                                ->setRaw($raw);

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
