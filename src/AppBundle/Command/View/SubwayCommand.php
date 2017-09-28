<?php

namespace AppBundle\Command\View;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class SubwayCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:view:subway');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = $this->getContainer()->getParameter('kernel.root_dir') . '/fixtures';
        foreach (glob($dir . '/subway_*') as $item) {


            $file = file_get_contents($item);

            $file_name = basename($item);

            $list  = Yaml::parse($file);
            $lines = [];

            $subways = [];
            foreach ($list as $l) {

                if (array_key_exists($l['name'], $subways)) {
                    echo $file_name . ' ' . $l['name'] . PHP_EOL;
                }
                $subways[$l['name']] = $l;
            }

            ksort($subways);

            foreach ($subways as $l) {
                $name     = str_replace('_', '-', $file_name);
                $name     = str_replace('.yml', '', $name);
                $name     = str_replace('subway-', '', $name);
                $lines [] = '<li><a href="/' . $name . '/{{= it.req.realty }}?subway=' . $l['id'] . '">' . $l['name'] . '</a></li>';
            }

            $block_footer = '';
            foreach ($lines as $line) {
                $block_footer .= $line . PHP_EOL;
            }

            file_put_contents('subway_' . $file_name . '.html.dot', $block_footer);

            $map_lines = [];
            foreach ($subways as $l) {

                $line = '<li data-id="' . $l['id'] . '" data-color="' . implode(',', $l['color']) . '" data-name="' . $l['name'] . '" class="subway-station {{? it.req.subway.indexOf(\'' . $l['id'] . '\') !== -1 }}active{{?}}">';
                foreach ($l['color'] as $color) {
                    $line .= '<div class="color" style="background-color: ' . $color . '"></div>';
                }
                $line         .= '<span>' . $l['name'] . '</span></li>';
                $map_lines [] = $line;
            }

            $name      = str_replace('_', '-', $file_name);
            $name      = str_replace('.yml', '', $name);
            $block_map = '<div class="' . $name . '">' . PHP_EOL . '<ul>' . PHP_EOL;
            foreach ($map_lines as $line) {
                $block_map .= '    ' . $line . PHP_EOL;
            }
            $block_map .= '<ul>' . PHP_EOL . '</div>';

            file_put_contents('map_' . $file_name . '.html.dot', $block_map);
        }
    }
}
