<?php

namespace App\Command;

use App\Collector\CollectorFactory;
use App\Document\City\CityModel;
use App\Document\Parse\Record\RecordModel;
use App\Queue\Message\ParseMessage;
use App\Queue\Producer\ParseProducer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CollectCommand extends Command
{
    protected static $defaultName = 'app:collect';

    private $logger;
    private $collectorFactory;
    private $recordModel;
    private $cityModel;
    private $parseProducer;

    public function __construct(
        LoggerInterface $logger,
        CollectorFactory $collectorFactory,
        RecordModel $recordModel,
        CityModel $cityModel,
        ParseProducer $parseProducer
    )
    {
        $this->logger           = $logger;
        $this->collectorFactory = $collectorFactory;
        $this->recordModel      = $recordModel;
        $this->cityModel        = $cityModel;
        $this->parseProducer    = $parseProducer;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addOption(
                'record',
                null,
                InputOption::VALUE_OPTIONAL
            )->addOption(
                'city',
                null,
                InputOption::VALUE_OPTIONAL,
                'sankt-peterburg',
                'sankt-peterburg'
            )->addOption(
                'valid-period',
                null,
                InputOption::VALUE_OPTIONAL,
                '1 day',
                '1 day'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!empty($input->getOption('city'))) {
            $city = $this->cityModel->findOneByShortName($input->getOption('city'));
            if ($city === null) {
                $output->writeln(sprintf('<error>There is no city with short name %s</error>', $input->getOption('city')));
                exit(1);
            }
            $records = $this->recordModel->findByCity($city->getShortName());
        } else {
            $records = $this->recordModel->findAll();
        }

        if (empty($records)) {
            $this->logger->error('There are no records');

            return;
        }

        $record_id = $input->getOption('record');

        $totalItems = 0;
        foreach ($records as $record) {

            if (!empty($record_id) && $record_id !== $record->getId()) {

                continue;
            }

            $this->logger->info('Collect record', [
                'recordId' => $record->getId(),
                'cityName' => $record->getCity(),
            ]);

            foreach ($record->getSources() as $source) {

                $collector = $this->collectorFactory->init($source);
                while (true) {

                    $this->logger->info('Collecting source...', [
                        'cityName'         => $record->getCity(),
                        'SourceType'       => $source->getType(),
                        'SourceParameters' => $source->getParameters()
                    ]);

                    try {
                        $result = $collector->collect($source, $input->getOption('valid-period'));
                    } catch (\Exception $e) {

                        $this->logger->error('Collecting source error', [
                            'cityName'         => $record->getCity(),
                            'SourceType'       => $source->getType(),
                            'SourceParameters' => $source->getParameters(),
                            'exception'        => $e->getMessage()
                        ]);

                        break;

                    }

                    $this->logger->info('Collecting source result', [
                        'cityName'         => $record->getCity(),
                        'SourceType'       => $source->getType(),
                        'SourceParameters' => $source->getParameters(),
                        'resultItems'      => count($result->getItems()),
                        'resultDone'       => $result->isDone()
                    ]);

                    foreach ($result->getItems() as $item) {
                        $totalItems++;
                        $this->parseProducer->publish(
                            (new ParseMessage())
                                ->setSource($source)
                                ->setRaw($item)
                        );
                    }

                    if ($result->isDone()) {

                        $this->logger->info('Collecting source done', [
                            'cityName'         => $record->getCity(),
                            'SourceType'       => $source->getType(),
                            'SourceParameters' => $source->getParameters()
                        ]);

                        break;
                    }

                    gc_collect_cycles();
                }
            }
        }

        $this->logger->debug('Total items', [
            'totalItems' => $totalItems
        ]);
    }
}
