<?php

namespace AppBundle\Model\Logic\Publisher;

use AppBundle\Model\Document\City\SubwayModel;
use Schema\Note\Note;
use Schema\City\Subway;

class NoteFormatter
{
    private $subways;

    /**
     * MessageFormatter constructor.
     * @param SubwayModel $model_subway
     */
    public function __construct(SubwayModel $model_subway)
    {
        $this->initSubways($model_subway->findAll());
    }

    /**
     * @param array $subways
     * @return bool
     */
    private function initSubways(array $subways)
    {
        $this->subways = [];
        foreach ($subways as $subway) {
            $this->subways[$subway->getId()] = $subway;
        }

        return true;
    }

    /**
     * @param Note $note
     * @return string
     */
    public function formatType(Note $note)
    {
        $type_string = '';
        switch ($note->getType()) {
            case Note::TYPE_ROOM:
                $type_string = 'комната';
                break;
            case Note::TYPE_FLAT_1:
                $type_string = '1 комнатная квартира';
                break;
            case Note::TYPE_FLAT_2:
                $type_string = '2 комнатная квартира';
                break;
            case Note::TYPE_FLAT_3:
                $type_string = '3 комнатная квартира';
                break;
            case Note::TYPE_FLAT_N:
                $type_string = '4+ комнатная квартира';
                break;
            case Note::TYPE_STUDIO:
                $type_string = 'студия';
                break;
        }

        return $type_string;
    }

    /**
     * @param Note $note
     * @return string
     */
    public function formatLink(Note $note)
    {
        $link = 'https://socrent.ru/rent/saint-petersburg/';
        switch ($note->getType()) {
            case Note::TYPE_ROOM:
                $link .= 'komnaty/room-p';
                break;
            case Note::TYPE_FLAT_1:
                $link .= 'kvartiry/1-k-kvartira-p';
                break;
            case Note::TYPE_FLAT_2:
                $link .= 'kvartiry/2-k-kvartira-p';
                break;
            case Note::TYPE_FLAT_3:
                $link .= 'kvartiry/3-k-kvartira-p';
                break;
            case Note::TYPE_FLAT_N:
                $link .= 'kvartiry/4-k-kvartira-p';
                break;
            case Note::TYPE_STUDIO:
                $link .= 'kvartiry/studia-p';
                break;
        }

        return $link . '.' . $note->getId();
    }

    /**
     * @param Note $note
     * @return string[]
     */
    public function formatSubways(Note $note)
    {
        $subways = [];
        foreach ($note->getSubways() as $subway_id) {
            if (!array_key_exists($subway_id, $this->subways)) {
                continue;
            }

            $subway = $this->subways[$subway_id];

            if (!($subways instanceof Subway)) {

                continue;
            }

            $subways[] = $subway->getName();
        }

        return $subways;
    }
}
