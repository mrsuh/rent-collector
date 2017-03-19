<?php

namespace AppBundle\Command\Test;

use AppBundle\Model\Parser\Area\TextAreaParser;
use AppBundle\Service\TomitaService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class AreaCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:test:area');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tests = Yaml::parse(file_get_contents($this->getContainer()->getParameter('file.test.texts')));

        $bin    = $this->getContainer()->getParameter('bin.tomita');
        $config = $this->getContainer()->getParameter('file.config.tomita.area');
        $tomita = new TomitaService($bin, $config);
        $parser = new TextAreaParser($tomita);

        $success = 0;
        $index   = 0;
        $count   = count($tests);
        foreach ($tests as $key => $test) {

            $text = $test['text'];

            $text = 'Сдается комната 15 кв.м в 2-х комнатной квартире, расположенная по адресу м.Гражданский проспект, ул.Гражданский проспект д.124 корп.3. 7 минут ходьбы до метро. Раздельный санузел, телевизор, стиральная машина, холодильник, микроволновка, интернет, комнатная мебель, кухонная мебель, сушилка для белья, есть кое какая посуда, рядом парк, рядом детсад, рядом школа, рядом гипермаркет. Хороший ремонт. ТОЛЬКО ДЛЯ ОДНОГО ПАРНЯ ДО 30 ЛЕТ, БЕЗ ЖИВОТНЫХ, ЧИСТОПЛОТНЫЙ И БЕЗ ВРЕДНЫХ ПРИВЫЧЕК (ТОЛЬКО РУССКИЕ). Цена вопроса 13000 тысяч + ку, выходит около 15000"+" "-" тысяч в зависимости от сезона. Соседи я и мой молодой человек. Залог 13000, который возвращается при выезде. Заезд 15/12';

            $area = $parser->parseText($text);

            if (null !== $area) {
                $success++;
            }

            $str = sprintf('[ %d / %d ] %d %s pt', $count, $key, $success, number_format(($success * 100 / (++$index)), 2));
            $output->writeln('<info>' . $str . '</info>');
        }

        $output->writeln(Date('Y-m-d H:i:s') . ' | ' . $index . ' / ' . $success . ' / ' . number_format(($success * 100 / $index), 2) . '%');
    }
}