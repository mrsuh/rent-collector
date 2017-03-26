<?php

namespace AppBundle\Command;

use AppBundle\Command\Helper\DisplayTrait;
use AppBundle\ODM\Document\Note;
use Jenssegers\ImageHash\ImageHash;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class TestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:test');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //https://pp.userapi.com/c636318/v636318613/54de2/kIq5DhqS5VY.jpg
        //https://pp.userapi.com/c638521/v638521306/26768/pQ_C8gtJcpw.jpg

        $hasher = new ImageHash();
        $hash1  = $hasher->hash('https://pp.userapi.com/c636318/v636318613/54de2/kIq5DhqS5VY.jpg');
        $hash2  = $hasher->hash('https://pp.userapi.com/c638521/v638521306/26768/pQ_C8gtJcpw.jpg');

        $distance = $hasher->distance($hash1, $hash2);

        echo $distance . PHP_EOL;
        echo $hash1 . PHP_EOL;
        echo $hash2 . PHP_EOL;
    }
}
