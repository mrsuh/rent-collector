<?php

namespace AppBundle\Command\Init;

use AppBundle\ODM\Document\Subway;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class SubwayCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:init:subway');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $list = Yaml::parse(file_get_contents($this->getContainer()->getParameter('file.fixtures.subway')));

        $dm_factory = $this->getContainer()->get('odm.data.mapper.factory');
        $dm_subway  = $dm_factory->init(Subway::class);
        $dm_subway->drop();
        foreach ($list as $key => $val) {
            $dm_subway->insert(
                (new Subway())
                    ->setId($val['id'])
                    ->setName($val['name'])
                    ->setRegexp($val['regexp'])
                    ->setColor($val['color'])
                    ->setCity($val['city'])
            );
        }
    }
}
