<?php

namespace AppBundle\Command;

use AppBundle\Command\Helper\DisplayTrait;
use AppBundle\ODM\Document\Note;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilterCommand extends ContainerAwareCommand
{
    use DisplayTrait;

    protected function configure()
    {
        $this->setName('app:filter');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm_factory = $this->getContainer()->get('odm.hot.data.mapper.factory');
        $dm_note = $dm_factory->init(Note::class);

        $filter_black_list_person      = $this->getContainer()->get('filter.black_list.person');
        $filter_black_list_description = $this->getContainer()->get('filter.black_list.description');
        $duplicate_ids                 = [];

        $count   = 0;

        foreach ($dm_note->find() as $note) {

            if (in_array($note->getId(), $duplicate_ids)) {
                continue;
            }

            if (!$filter_black_list_description->isAllow($note)) {
                $this->debug($note->getId() . ' filter by black list description');
                $count++;
                $dm_note->delete($note);
                unset($note);
                continue;
            }

            if (!$filter_black_list_person->isAllow($note)) {
                $this->debug($note->getId() . ' filter by black list person');
                $count++;
                $dm_note->delete($note);
                unset($note);
                continue;
            }

            $duplicates = $dm_note->find([
                'description_hash' => $note->getDescriptionHash(),
                'id'               => [
                    '$ne' => $note->getId()
                ]
            ]);

            foreach ($duplicates as $duplicate) {
                $this->debug($duplicate->getId() . ' filter by unique description');
                $duplicate_ids[] = $duplicate->getId();
                $count++;
                $dm_note->delete($duplicate);
            }

            $duplicates = $dm_note->find([
                'external_id' => $note->getExternalId(),
                'id'          => [
                    '$ne' => $note->getId()
                ]
            ]);

            foreach ($duplicates as $duplicate) {
                $this->debug($duplicate->getId() . ' filter by unique external id');
                $duplicate_ids[] = $duplicate->getId();
                $count++;
                $dm_note->delete($duplicate);
            }
        }

        $this->debug('Total filtered: ' . $count);
    }
}
