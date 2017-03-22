<?php

namespace AppBundle\Command\Test;

use AppBundle\Model\Parser\Type\TextTypeParser;
use AppBundle\Service\TomitaService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class TypeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:test:type');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tests = Yaml::parse(file_get_contents($this->getContainer()->getParameter('file.test.texts')));

        $bin    = $this->getContainer()->getParameter('bin.tomita');
        $config = $this->getContainer()->getParameter('file.config.tomita.type');
        $tomita = new TomitaService($bin, $config);
        $parser = new TextTypeParser($tomita, $config);

        $success = 0;
        $index   = 0;
        $errors  = [];
        $count   = count($tests);
        foreach ($tests as $key => $test) {

            $text = $test['text'];
            $type = $parser->parseText($text);

            if ((int)$test['type'] === $type) {
                $success++;
            } else {
                $errors[] = $test;
            }

            $str = sprintf('[ %d / %d ] %d %s pt', $count, $index, $success, number_format(($success * 100 / (++$index)), 2));
            $output->writeln('<info>' . $str . '</info>');
        }

        file_put_contents('errors_type.yml', Yaml::dump($errors, 2));
        $output->writeln(Date('Y-m-d H:i:s') . ' | ' . $index . ' / ' . $success . ' / ' . number_format(($success * 100 / $index), 2) . '%');
    }
}