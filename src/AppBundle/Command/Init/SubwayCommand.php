<?php

namespace AppBundle\Command\Init;

use ODM\DocumentManager\DocumentManager;
use Schema\City\Subway;
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
        $dir        = $this->getContainer()->getParameter('kernel.root_dir') . '/fixtures';
        $dm_factory = $this->getContainer()->get('dm');
        $dm_subway  = $dm_factory->init(Subway::class);
        foreach (glob($dir . '/subway_*') as $file_path) {
            $this->load($dm_subway, $file_path);
        }
    }

    private function load(DocumentManager $dm_subway, $file_path)
    {
        $list = Yaml::parse(file_get_contents($file_path));

        foreach ($list as $key => $val) {

            $subway = (new Subway())
                ->setId($val['id'])
                ->setName($val['name'])
                ->setRegexp($val['regexp'])
                ->setCity($val['city']);

            foreach ($val['color'] as $color) {
                $subway->addColor($color);
            }

            $dm_subway->insert($subway);
        }

        return true;
    }
}
