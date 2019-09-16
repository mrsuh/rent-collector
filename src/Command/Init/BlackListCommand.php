<?php

namespace App\Command\Init;

use App\Document\BlackList\BlackListModel;
use Schema\BlackList\Record;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class BlackListCommand extends Command
{
    protected static $defaultName = 'app:init:black-list';

    private $blackListModel;

    public function __construct(BlackListModel $blackListModel)
    {
        $this->blackListModel = $blackListModel;
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('filePath', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $list = Yaml::parse(file_get_contents($input->getArgument('filePath')));
        foreach ($list as $key => $val) {

            $record = (new Record())
                ->setType(Record::TYPE_DESCRIPTION)
                ->setRegexp($val);

            $this->blackListModel->create($record);
        }

        return true;
    }
}
