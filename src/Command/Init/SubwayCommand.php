<?php

namespace App\Command\Init;

use App\Document\City\SubwayModel;
use Schema\City\Subway;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class SubwayCommand extends Command
{
    protected static $defaultName = 'app:init:subway';

    private $subwayModel;

    public function __construct(SubwayModel $subwayModel)
    {
        $this->subwayModel = $subwayModel;

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

            $subway = (new Subway())
                ->setId($val['id'])
                ->setName($val['name'])
                ->setRegexp($val['regexp'])
                ->setCity($val['city']);

            foreach ($val['color'] as $color) {
                $subway->addColor($color);
            }

            $this->subwayModel->create($subway);
        }
    }
}
