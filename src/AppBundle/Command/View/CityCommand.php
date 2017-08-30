<?php

namespace AppBundle\Command\View;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class CityCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:view:city');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file_ = $this->getContainer()->getParameter('kernel.root_dir') . '/fixtures/city.yml';
        $file  = file_get_contents($file_);

        $msk    = null;
        $spb    = null;
        $cities = [];
        foreach (Yaml::parse($file) as $c) {

            if ($c['short_name'] === 'moskva') {
                $msk = $c;

                continue;
            }

            if ($c['short_name'] === 'sankt-peterburg') {
                $spb = $c;

                continue;
            }

            $cities[$c['name']] = $c;
        }

        ksort($cities);
        $cities = array_values($cities);

        array_unshift($cities, $spb, $msk);

        $lines = [];
        foreach ($cities as $index => $city) {
            $lines [] = '<li><a href="/' . $city['short_name'] . '/{{= it.req.realty }}">' . $city['name'] . '</a></li>';
        }

        $block_footer = '';
        $chink        = ceil(count($lines) / 4);
        foreach (array_chunk($lines, $chink) as $chunk) {
            $block_footer .= '<ul>' . PHP_EOL;
            foreach ($chunk as $line) {
                $block_footer .= '    ' . $line . PHP_EOL;
            }
            $block_footer .= '</ul>' . PHP_EOL . PHP_EOL;
        }
        file_put_contents('city_block_footer.html', $block_footer);

        $lines = [];
        foreach ($cities as $index => $city) {
            $lines [] = '<li><a href="/' . $city['short_name'] . '/{{= it.req.realty }}" class="city {{= it.req.city === \'' . $city['short_name'] . '\' ? \'active\' : \'\' }}" data-value="' . $city['short_name'] . '">' . $city['name'] . '</a></li>';
        }
        $block_city = '';

        $block_city .= '<ul>' . PHP_EOL;
        foreach ($lines as $line) {
            $block_city .= '    ' . $line . PHP_EOL;
        }
        $block_city .= '</ul>' . PHP_EOL . PHP_EOL;

        file_put_contents('city_block_city.html', $block_city);

        $search_results_head = '';
        foreach ($cities as $index => $city) {
            $search_results_head .= "{{?" . ($index === 0 ? '' : '?') . " it.req.city === '" . $city['short_name'] . "' }}" . PHP_EOL;
            $search_results_head .= '    ' . $city['name'] . PHP_EOL;
        }
        $search_results_head .= '{{?}}';
        file_put_contents('city_search_results_head.html', $search_results_head);
    }
}
