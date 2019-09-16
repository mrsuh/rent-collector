<?php

namespace App\Command\Init;

use App\Document\City\CityModel;
use Schema\City\City;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class CityCommand extends Command
{
    protected static $defaultName = 'app:init:city';

    private $cityModel;

    public function __construct(CityModel $cityModel)
    {
        $this->cityModel = $cityModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('filePath', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $list = Yaml::parse(file_get_contents($input->getArgument('filePath')));
        foreach ($list as $key => $val) {

            $city = (new City())
                ->setId($val['id'])
                ->setName($val['name'])
                ->setShortName($val['short_name']);

            $this->cityModel->create($city);
        }
    }
}
