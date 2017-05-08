<?php

namespace AppBundle\Command\Test;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
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

        $explorer = $this->getContainer()->get('explorer.tomita');

        $success = 0;
        $index   = 0;
        $errors  = [];
        $count   = count($tests);

        $style = new OutputFormatterStyle('red');
        $output->getFormatter()->setStyle('fire', $style);

        foreach ($tests as $key => $test) {

            $text = $test['text'];

            $ex = $explorer->explore($text);

            $tag = 'fire';
            if ((int)$test['type'] === $ex->getType()) {
                $success++;
                $tag = 'info';
            } else {
                $errors[] = $test;
            }

            $index++;

            $str = sprintf('[ %d / %d ] %d %s %%', $count, $index, $success, number_format(($success * 100 / $index), 2));
            $output->writeln('<' . $tag . '>' . $str . '</' . $tag . '>');
        }

        file_put_contents('errors_type.yml', Yaml::dump($errors, 2));
        $output->writeln(Date('Y-m-d H:i:s') . ' | ' . $index . ' / ' . $success . ' / ' . number_format(($success * 100 / $index), 2) . '%');
    }
}