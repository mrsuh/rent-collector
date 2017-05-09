<?php

namespace AppBundle\Command\Test;

use AppBundle\Model\Parser\Subway\TextSubwayParser;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class SubwayCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:test:subway');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tests    = Yaml::parse(file_get_contents($this->getContainer()->getParameter('file.test.texts')));
        $explorer = $this->getContainer()->get('explorer.subway');

        $success = 0;
        $index   = 0;
        foreach ($tests as $key => $test) {
            $text    = mb_strtolower($test['text']);
            $subways = $explorer->explore($text);

            if (count($subways) > 0) {
                $success++;
            }

            $index++;
        }

        $output->writeln(Date('Y-m-d H:i:s') . ' | ' . $index . ' / ' . $success . ' / ' . number_format(($success * 100 / $index), 2) . '%');
    }
}