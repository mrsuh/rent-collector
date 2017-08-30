<?php

namespace AppBundle\Command\Init;

use Schema\City\City;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class CityCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:init:city');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file_path  = $this->getContainer()->getParameter('kernel.root_dir') . '/fixtures/city.yml';
        $list       = Yaml::parse(file_get_contents($file_path));
        $dm_factory = $this->getContainer()->get('dm.hot');
        $dm_city    = $dm_factory->init(City::class);
        foreach ($list as $key => $val) {

            $city = (new City())
                ->setId($val['id'])
                ->setName($val['name'])
                ->setShortName($val['short_name']);

            $dm_city->insert($city);
        }

        return true;
    }
}
