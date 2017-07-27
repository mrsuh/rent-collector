<?php

namespace AppBundle\Model\Logic\Publisher;

use ODM\DocumentManager\DocumentManagerFactory;
use Schema\Note\Note;
use Schema\City\Subway;
use AppBundle\Request\VkPrivateRequest;
use Monolog\Logger;

class VkPublisher implements PublisherInterface
{
    private $request;
    private $subways;
    private $logger;
    private $params;
    private $dm_note;

    /**
     * VkPublisher constructor.
     * @param VkPrivateRequest       $request
     * @param DocumentManagerFactory $dm
     * @param Logger                 $logger
     * @param array                  $params
     */
    public function __construct(VkPrivateRequest $request, DocumentManagerFactory $dm, Logger $logger, array $params)
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
    private function findPublishedNotesCountLastHour()
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
            $date = \DateTime::createFromFormat('U', $note->getPublishedTimestamp());

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

            $this->logger->debug('There are no subways or price or photo', [
                'note_id'          => $note->getId(),
                'note_external_id' => $note->getExternalId()
            ]);

            return false;
        }

        if ($this->findPublishedNotesCountLastHour() >= 4) {

            $this->logger->debug('Limitation of publications at this hour', [
                'note_id'          => $note->getId(),
                'note_external_id' => $note->getExternalId()
            ]);

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


            $contact = $note->getContact();

            if (empty($contact->getExternalId()) || empty($contact->getName())) {

                $this->logger->debug('Empty contact id or name', [
                    'note_id'          => $note->getId(),
                    'note_external_id' => $note->getExternalId()
                ]);

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
                '[' . $contact->getExternalId() . '| ' . $contact->getName() . ']' .
                PHP_EOL .
                $this->getLink($note);

            $photos = [];
            foreach ($note->getPhotos() as $photo) {

                $this->logger->debug('Uploading photo...', [
                    'note_id'          => $note->getId(),
                    'note_external_id' => $note->getExternalId()
                ]);

                $photo_id = $this->uploadPhoto($photo['high']);

                $this->logger->debug('Uploading photo... done', [
                    'note_id'          => $note->getId(),
                    'note_external_id' => $note->getExternalId()
                ]);

                if (null === $photo_id) {

                    $this->logger->error('Uploading photo... nullable photo id', [
                        'note_id'          => $note->getId(),
                        'note_external_id' => $note->getExternalId()
                    ]);

                    continue;
                }

                $photos[] = 'photo' . $this->params['user_id'] . '_' . $photo_id;
            }

            $this->logger->debug('Publishing sleep...', [
                'note_id'          => $note->getId(),
                'note_external_id' => $note->getExternalId()
            ]);

            usleep(200000);

            $this->logger->debug('Publishing sleep... done', [
                'note_id'          => $note->getId(),
                'note_external_id' => $note->getExternalId()
            ]);

            $this->logger->debug('Publishing post...', [
                'note_id'          => $note->getId(),
                'note_external_id' => $note->getExternalId()
            ]);

            $this->request->wallPost([
                'owner_id'    => '-' . $this->params['group_id'],
                'from_group'  => 1,
                'message'     => $prefix . $note->getDescription() . $postfix,
                'attachments' => implode(',', $photos),
                'guid'        => $note->getId()
            ]);

            $this->logger->debug('Publishing post... done', [
                'note_id'          => $note->getId(),
                'note_external_id' => $note->getExternalId()
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

