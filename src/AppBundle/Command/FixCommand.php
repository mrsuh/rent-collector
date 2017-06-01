<?php

namespace AppBundle\Command;

use AppBundle\Command\Helper\DisplayTrait;
use AppBundle\Document\Note;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixCommand extends ContainerAwareCommand
{
    use DisplayTrait;

    protected function configure()
    {
        $this->setName('app:fix');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $dm_note = $this->getContainer()->get('odm.hot.data.mapper.factory')->init(Note::class);
        $count   = 0;
        foreach ($dm_note->find() as $note) {
            $contacts = $note->getContacts();
            if ($contacts['person']['write'] === 'contacts.person.link') {
                $this->debug($note->getId());
                $count++;

                $contacts['person']['write'] = $contacts['person']['link'];
                $note->setContacts($contacts);
                $dm_note->update($note);
            }
        }

        $dm_note_cold = $this->getContainer()->get('odm.cold.data.mapper.factory')->init(Note::class);
        foreach ($dm_note_cold->find() as $note) {
            $contacts = $note->getContacts();
            if ($contacts['person']['write'] === 'contacts.person.link') {
                $this->debug($note->getId());
                $count++;

                $contacts['person']['write'] = $contacts['person']['link'];
                $note->setContacts($contacts);
                $dm_note_cold->update($note);
            }
        }

        $this->debug('total ' . $count);
    }
}
