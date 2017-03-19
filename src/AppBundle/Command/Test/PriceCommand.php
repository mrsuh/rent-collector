<?php

namespace AppBundle\Command\Test;

use AppBundle\Model\Parser\Price\TextPriceParser;

;
use AppBundle\Service\TomitaService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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
        $tests = Yaml::parse(file_get_contents($this->getContainer()->getParameter('file.test.texts')));

        $bin    = $this->getContainer()->getParameter('bin.tomita');
        $config = $this->getContainer()->getParameter('file.config.tomita.price');
        $tomita = new TomitaService($bin, $config);
        $parser = new TextPriceParser($tomita);

        $success = 0;
        $index   = 0;
        $count   = count($tests);
        $errors  = [];
        foreach ($tests as $key => $test) {

            $text = $test['text'];

            if (6 === $test['type']) {
                continue;
            }

            if (count($test['price']) === 0) {
                continue;
            }

            $price = $parser->parseText($text);

            if (in_array($price, $test['price'])) {
                $success++;
            } else {
                $errors[] = $test;
            }

            $str = sprintf('[ %d / %d ] %d %s pt', $count, $key, $success, number_format(($success * 100 / (++$index)), 2));
            $output->writeln('<info>' . $str . '</info>');
        }

        file_put_contents('price_errors.yml', Yaml::dump($errors, 2));
        $output->writeln(Date('Y-m-d H:i:s') . ' | ' . $index . ' / ' . $success . ' / ' . number_format(($success * 100 / $index), 2) . '%');
    }
}