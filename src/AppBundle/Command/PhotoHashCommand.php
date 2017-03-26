<?php

namespace AppBundle\Command;

use AppBundle\Command\Helper\DisplayTrait;
use AppBundle\ODM\Document\Note;
use Jenssegers\ImageHash\ImageHash;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class PhotoHashCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:photo:hash')
            ->addArgument('url');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url  = $input->getArgument('url');

        if (empty($url)) {
            $output->writeln('<error>Empty url</error>');

            return false;
        }

        $hasher = new ImageHash();
        $hash = $hasher->hash($url);

        $output->writeln('<info>' . $hash . '</info>');
    }
}
