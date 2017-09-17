<?php

namespace AppBundle\Command\Test;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class PriceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:test:price');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tests = Yaml::parse(file_get_contents('/Users/newuser/web/go/src/rent-parser/tests/tests.yml'));

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

            if (6 === (int)$test['price']) {

                continue;
            }

            $tag = 'fire';
            if (in_array($ex->getPrice(), $test['price'], true) || (count($test['price']) === 0 && -1 === $ex->getPrice())) {
                $success++;
                $tag = 'info';
            } else {
                $test['tomita'] = $ex->getPrice();
                $errors[]       = $test;
            }

            $index++;

            $str = sprintf('[ %d / %d ] %d %s %%', $count, $index, $success, number_format(($success * 100 / $index), 2));
            $output->writeln('<' . $tag . '>' . $str . '</' . $tag . '>');
        }

        file_put_contents('errors_price.yml', Yaml::dump($errors, 2));
        $output->writeln(Date('Y-m-d H:i:s') . ' | ' . $index . ' / ' . $success . ' / ' . number_format(($success * 100 / $index), 2) . '%');
    }
}