<?php

namespace App\Command;

use App\Collector\CollectorFactory;
use App\Document\City\CityModel;
use App\Document\Parse\Record\RecordModel;
use App\Queue\Message\ParseMessage;
use App\Queue\Producer\ParseProducer;
use Psr\Log\LoggerInterface;
use Schema\City\City;
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
    ) {
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
                'city',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'sankt-peterburg',
                ['sankt-peterburg']
            )->addOption(
                'valid-period',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                '1 hour',
                ['1 hour']
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cities       = $input->getOption('city');
        $validPeriods = $input->getOption('valid-period');

        if (count($cities) !== count($validPeriods)) {
            $output->writeln('<error>Count cities !== count validPeriods</error>');

            exit(1);
        }

        foreach ($cities as $index => $cityName) {
            $city = $this->cityModel->findOneByShortName($cityName);
            if ($city === null) {
                $output->writeln(
                    sprintf('<error>There is no city with short name "%s"</error>', $cityName)
                );
                continue;
            }

            $validPeriod = $validPeriods[$index];

            $this->collect($city, $validPeriod);
        }
    }

    private function collect(City $city, string $validPeriod): void
    {
        $records = $this->recordModel->findByCity($city->getShortName());

        if (empty($records)) {
            $this->logger->error(
                'There are no records',
                [
                    'city'        => $city->getShortName(),
                    'validPeriod' => $validPeriod,
                ]
            );

            return;
        }

        $this->logger->info(
            'Collecting notes',
            [
                'city'        => $city->getShortName(),
                'validPeriod' => $validPeriod,
            ]
        );

        $totalItems = 0;
        foreach ($records as $record) {

            if (!empty($record_id) && $record_id !== $record->getId()) {

                continue;
            }

            $this->logger->debug(
                'Collect record',
                [
                    'recordId'    => $record->getId(),
                    'city'        => $city->getShortName(),
                    'validPeriod' => $validPeriod,
                ]
            );

            foreach ($record->getSources() as $source) {

                $collector = $this->collectorFactory->init($source);
                while (true) {

                    $this->logger->debug(
                        'Collecting source...',
                        [
                            'city'             => $city->getShortName(),
                            'validPeriod'      => $validPeriod,
                            'SourceType'       => $source->getType(),
                            'SourceParameters' => $source->getParameters(),
                        ]
                    );

                    try {
                        $result = $collector->collect($source, $validPeriod);
                    } catch (\Exception $e) {

                        $this->logger->error(
                            'Collecting source error',
                            [
                                'city'             => $city->getShortName(),
                                'validPeriod'      => $validPeriod,
                                'SourceType'       => $source->getType(),
                                'SourceParameters' => $source->getParameters(),
                                'exception'        => $e->getMessage(),
                            ]
                        );

                        break;

                    }

                    $this->logger->debug(
                        'Collecting source result',
                        [
                            'city'             => $city->getShortName(),
                            'validPeriod'      => $validPeriod,
                            'SourceType'       => $source->getType(),
                            'SourceParameters' => $source->getParameters(),
                            'resultItems'      => count($result->getItems()),
                            'resultDone'       => $result->isDone(),
                        ]
                    );

                    foreach ($result->getItems() as $item) {
                        $totalItems++;
                        $this->parseProducer->publish(
                            (new ParseMessage())
                                ->setSource($source)
                                ->setRaw($item)
                        );
                    }

                    if ($result->isDone()) {

                        $this->logger->debug(
                            'Collecting source done',
                            [
                                'city'             => $city->getShortName(),
                                'validPeriod'      => $validPeriod,
                                'SourceType'       => $source->getType(),
                                'SourceParameters' => $source->getParameters(),
                            ]
                        );

                        break;
                    }

                    gc_collect_cycles();
                }
            }
        }

        $this->logger->info(
            'Collecting notes done',
            [
                'city'        => $city->getShortName(),
                'validPeriod' => $validPeriod,
                'notes'       => $totalItems,
            ]
        );
    }
}
