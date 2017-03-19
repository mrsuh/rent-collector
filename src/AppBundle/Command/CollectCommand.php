<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

class CollectCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:collect');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command_pre = $this->getApplication()->find('app:collect:pre');

        $input_pre = new ArrayInput([]);
        $command_pre->run($input_pre, $output);

        $command_post = $this->getApplication()->find('app:collect:post');

        $input_post = new ArrayInput([]);
        $command_post->run($input_post, $output);
    }
}
