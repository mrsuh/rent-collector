<?php

namespace AppBundle\Command;

use AppBundle\Queue\Message\ParseMessage;
use Schema\Parse\Record\Source;
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
            )->addOption(
                'type',
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

        $record_id   = $input->getOption('record');
        $source_type = $input->getOption('type');

        if (empty($records)) {
            $logger->error('There are no records');

            return false;
        }

        $count = 0;
        foreach ($records as $record) {

            if (!empty($record_id) && $record_id !== $record->getId()) {

                $logger->debug('Record filter by id', [
                    'id' => $record->getId()
                ]);

                continue;
            }

            $logger->info('Collect record', [
                'record' => $record->getId(),
                'city'   => $record->getCity(),
            ]);

            foreach ($record->getSources() as $source) {

                if (!empty($source_type)) {
                    switch ($source_type) {
                        case 'vk':

                            if (!in_array($source->getType(), [Source::TYPE_VK_COMMENT, Source::TYPE_VK_WALL])) {

                                $logger->debug('Source filter by type', [
                                    'type' => $source->getType()
                                ]);

                                continue 2;
                            }

                            break;
                        case 'avito':

                            if (Source::TYPE_AVITO !== $source->getType()) {

                                $logger->debug('Source filter by type', [
                                    'type' => $source->getType()
                                ]);

                                continue 2;
                            }

                            break;
                    }
                }

                $logger->info('Collect source', [
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

                        $message =
                            (new ParseMessage())
                                ->setSource($source)
                                ->setRaw($raw);

                        $producer->publish($message);
                    }

                    gc_collect_cycles();
                }
            }
        }

        $logger->debug('Total notes', [
            'total' => $count
        ]);
    }
}
