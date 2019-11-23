<?php

namespace App\Command\View;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class SubwayCommand extends Command
{
    protected static $defaultName = 'app:view:subway';

    protected function configure()
    {
        $this->addArgument('filePath', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $list = Yaml::parse(file_get_contents($input->getArgument('filePath')));
        $cities = [];
        foreach ($list as $subway) {
            $city = $subway['city'];
            if (!array_key_exists($city, $cities)) {
                $cities[$city] = [];
            }

            $cities[$city][$subway['name']] = $subway;
        }

        foreach ($cities as $city => &$subways) {
            ksort($subways);

            $blockFooter = '';
            $blockMap    = '<div class="'.$city.'">'.PHP_EOL.'<ul>'.PHP_EOL;
            foreach ($subways as $subway) {
                $blockFooter .= '<li><a href="/'.$city.'/{{= it.req.realty }}?subway='.$subway['id'].'">'.$subway['name'].'</a></li>'.PHP_EOL;

                $blockMap .= '<li data-id="'.$subway['id'].'" data-color="'.implode(
                        ',',
                        $subway['color']
                    ).'" data-name="'.$subway['name'].'" class="subway-station {{? it.req.subway.indexOf(\''.$subway['id'].'\') !== -1 }}active{{?}}">';
                foreach ($subway['color'] as $color) {
                    $blockMap .= '<div class="color" style="background-color: '.$color.'"></div>';
                }
                $blockMap .= '<span>'.$subway['name'].'</span></li>'.PHP_EOL;
            }
            $blockMap .= '<ul>'.PHP_EOL.'</div>';

            file_put_contents('subway_'.$city.'.html', $blockFooter);
            file_put_contents('map_'.$city.'.html', $blockMap);
        }
    }
}
