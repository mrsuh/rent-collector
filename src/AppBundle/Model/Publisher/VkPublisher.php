<?php

namespace AppBundle\Model\Publisher;

use AppBundle\Document\Note;
use AppBundle\Document\Subway;
use AppBundle\Request\VkPrivateRequest;
use Monolog\Logger;
use ODM\DocumentMapper\DataMapperFactory;

class VkPublisher implements PublisherInterface
{
    private $request;
    private $subways;
    private $logger;
    private $params;
    private $dm_note;

    /**
     * VkPublisher constructor.
     * @param VkPrivateRequest  $request
     * @param DataMapperFactory $dm
     * @param array             $params
     */
    public function __construct(VkPrivateRequest $request, DataMapperFactory $dm, Logger $logger, array $params)
    {
        $this->logger  = $logger;
        $this->request = $request;
        $this->params  = $params;

        $this->dm_note = $dm->init(Note::class);

        $this->initSubways($dm->init(Subway::class)->find());
    }

    /**
     * @return int
     */
    private function findPublishedNotesLastHour()
    {
        $notes = $this->dm_note->find([
            'publishedTimestamp' => [
                '$gte' => (new \DateTime())->modify('- 1 hour')->getTimestamp(),
                '$lte' => (new \DateTime())->getTimestamp()
            ],
            'published'          => true
        ]);

        $count = 0;
        $now   = new \DateTime();
        foreach ($notes as $note) {
            $date = \DateTime::createFromFormat('U', $note->getTimestamp());

            if (false === $date) {
                continue;
            }

            if ((int)$date->format('H') !== (int)$now->format('H')) {
                continue;
            }

            $count++;
        }

        return $count;
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
     * @param int $type
     * @return string
     */
    private function formatType(int $type): string
    {
        $type_string = '';
        switch ($type) {
            case Note::ROOM:
                $type_string = 'комната';
                break;
            case Note::FLAT_1:
                $type_string = '1 комнатная квартира';
                break;
            case Note::FLAT_2:
                $type_string = '2 комнатная квартира';
                break;
            case Note::FLAT_3:
                $type_string = '3 комнатная квартира';
                break;
            case Note::FLAT_N:
                $type_string = '4+ комнатная квартира';
                break;
            case Note::STUDIO:
                $type_string = 'студия';
                break;
        }

        return $type_string;
    }

    /**
     * @param Subway $subway
     * @return string
     */
    private function formatSubway(Subway $subway)
    {
        return $subway->getName();
    }

    /**
     * @param Note $note
     * @return string
     */
    private function getLink(Note $note)
    {
        $link = 'https://socrent.ru/rent/saint-petersburg/';
        switch ($note->getType()) {
            case Note::ROOM:
                $link .= 'komnaty/room-p';
                break;
            case Note::FLAT_1:
                $link .= 'kvartiry/1-k-kvartira-p';
                break;
            case Note::FLAT_2:
                $link .= 'kvartiry/2-k-kvartira-p';
                break;
            case Note::FLAT_3:
                $link .= 'kvartiry/3-k-kvartira-p';
                break;
            case Note::FLAT_N:
                $link .= 'kvartiry/4-k-kvartira-p';
                break;
            case Note::STUDIO:
                $link .= 'kvartiry/studia-p';
                break;
        }

        return $link . '.' . $note->getId();
    }


    /**
     * @param string $url
     * @return int
     */
    private function uploadPhoto(string $url): int
    {
        try {

            usleep(200000);
            $response = $this->request->photosGetWallUploadServer([
                'group_id' => $this->params['group_id']
            ]);

            $get_photo_server_response = json_decode($response->getBody()->getContents(), true);

            if (!isset($get_photo_server_response['response'])) {
                return null;
            }

            if (!isset($get_photo_server_response['response']['upload_url'])) {
                return null;
            }

            $upload_url = $get_photo_server_response['response']['upload_url'];

            usleep(200000);
            $response = $this->request->uploadPhoto($upload_url, [
                'name'     => 'photo',
                'contents' => fopen($url, 'r')
            ]);

            $send_photo_response = json_decode($response->getBody()->getContents(), true);

            foreach (['photo', 'server', 'hash'] as $key) {
                if (!isset($send_photo_response[$key])) {
                    return null;
                }
            }

            $photo  = stripslashes($send_photo_response['photo']);
            $server = $send_photo_response['server'];
            $hash   = $send_photo_response['hash'];

            usleep(200000);
            $response = $this->request->photosSaveWallPhoto([
                'photo'    => $photo,
                'server'   => $server,
                'hash'     => $hash,
                'group_id' => $this->params['group_id'],
            ]);

            $save_photo_response = json_decode($response->getBody()->getContents(), true);

            if (!isset($save_photo_response['response'])) {
                return null;
            }

            if (!isset($save_photo_response['response'][0])) {
                return null;
            }

            if (!isset($save_photo_response['response'][0]['id'])) {
                return null;
            }

            return $save_photo_response['response'][0]['id'];

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return null;
        }
    }

    /**
     * @param Note $note
     * @return bool
     */
    public function publish(Note $note): bool
    {
        if (
            empty($note->getSubways()) ||
            empty($note->getPrice()) ||
            count($note->getPhotos()) < 3
        ) {
            return false;
        }

        $notes = $this->findPublishedNotesLastHour();

        if (count($notes) >= 4) {

            return false;
        }

        try {

            $subways = [];
            foreach ($note->getSubways() as $subway_id) {
                if (!array_key_exists($subway_id, $this->subways)) {
                    continue;
                }

                $subway    = $this->subways[$subway_id];
                $subways[] = $this->formatSubway($subway);
            }


            $contact = $note->getContacts();

            if (!isset($contact['person'])) {

                return false;
            }

            if (!isset($contact['person']['name']) || !isset($contact['person']['link'])) {

                return false;
            }

            $name = $contact['person']['name'];
            preg_match('/id.+/', $contact['person']['link'], $match);
            $id = $match[0];

            if (empty($id)) {
                return false;
            }

            $prefix =
                $this->formatType($note->getType()) .
                ' за ' .
                $note->getPrice() .
                ' руб. около метро ' .
                implode(', ', $subways) .
                PHP_EOL . PHP_EOL;

            $postfix =
                PHP_EOL . PHP_EOL .
                '[' . $id . '| ' . $name . ']' .
                PHP_EOL .
                $this->getLink($note);

            $photos = [];
            foreach ($note->getPhotos() as $photo) {
                $photo_id = $this->uploadPhoto($photo['high']);

                if (null === $photo_id) {
                    continue;
                }

                $photos[] = 'photo' . $this->params['user_id'] . '_' . $photo_id;
            }

            usleep(200000);
            $this->request->wallPost([
                'owner_id'    => '-' . $this->params['group_id'],
                'from_group'  => 1,
                'message'     => $prefix . $note->getDescription() . $postfix,
                'attachments' => implode(',', $photos),
                'guid'        => $note->getId()
            ]);

            $note
                ->setPublished(true)
                ->setPublishedTimestamp((new \DateTime())->getTimestamp());
            $this->dm_note->update($note);

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return false;
        }

        return true;
    }
}

