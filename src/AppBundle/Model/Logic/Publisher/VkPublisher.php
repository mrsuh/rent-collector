<?php

namespace AppBundle\Model\Logic\Publisher;

use AppBundle\Model\Document\Note\NoteModel;
use Schema\City\City;
use Schema\Note\Note;
use AppBundle\Request\VkPrivateRequest;
use Monolog\Logger;
use Schema\Note\Photo;
use Schema\Publish\Record\Record;
use Schema\Publish\User\User;

class VkPublisher implements PublisherInterface
{
    private $request;
    private $formatter;
    private $logger;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Record
     */
    private $record;
    private $model_note;

    /**
     * VkPublisher constructor.
     * @param VkPrivateRequest $request
     * @param NoteFormatter    $formatter
     * @param NoteModel        $model_note
     * @param Record           $record
     * @param User             $user
     * @param Logger           $logger
     */
    public function __construct(
        VkPrivateRequest $request,
        NoteFormatter $formatter,
        NoteModel $model_note,
        Record $record,
        User $user,
        Logger $logger
    )
    {
        $this->request    = $request;
        $this->formatter  = $formatter;
        $this->model_note = $model_note;
        $this->record     = $record;
        $this->user       = $user;
        $this->logger     = $logger;
    }

    /**
     * @param Photo $photo
     * @return null|int
     */
    private function uploadPhoto(Photo $photo)
    {
        try {

            usleep(500000);
            $response = $this->request->photosGetWallUploadServer([
                'group_id' => $this->record->getGroupId()
            ]);

            $get_photo_server_response = json_decode($response->getBody()->getContents(), true);

            if (!isset($get_photo_server_response['response'])) {
                return null;
            }

            if (!isset($get_photo_server_response['response']['upload_url'])) {
                return null;
            }

            $upload_url = $get_photo_server_response['response']['upload_url'];

            usleep(500000);
            $response = $this->request->uploadPhoto($upload_url, [
                'name'     => 'photo',
                'contents' => fopen($photo->getHigh(), 'r')
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

            usleep(500000);
            $response = $this->request->photosSaveWallPhoto([
                'photo'    => $photo,
                'server'   => $server,
                'hash'     => $hash,
                'group_id' => $this->record->getGroupId()
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

            return (int)$save_photo_response['response'][0]['id'];

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return null;
        }
    }

    /**
     * @return int
     */
    private function findPublishedNotesCountLastHour(City $city)
    {
        $notes = $this->model_note->findPublishedNotesByCityForPeriod(
            $city,
            (new \DateTime())->modify('- 1 hour'),
            (new \DateTime())
        );

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
     * @param Note $note
     * @return bool
     */
    public function publish(Note $note)
    {
        try {

            if ($this->findPublishedNotesCountLastHour((new City())->setShortName($note->getCity())) >= 4) {

                $this->logger->debug('Limitation of publications at this hour', [
                    'note_id'          => $note->getId(),
                    'note_external_id' => $note->getExternalId(),
                    'city'             => $note->getCity()
                ]);

                return false;
            }

            $prefix  =
                $this->formatter->formatType($note) .
                ' за ' .
                $this->formatter->formatPrice((int)$note->getPrice()) .
                ' руб. ' .
                (!empty($note->getSubways()) ? 'около метро ' . implode(', ', $this->formatter->formatSubways($note)) : '') .
                PHP_EOL . PHP_EOL;
            $postfix =
                PHP_EOL . PHP_EOL .
                $note->getContact()->getName() .
                PHP_EOL .
                $note->getLink();

            $message = $prefix . $note->getDescription() . $postfix;

            $attachments = [];
            foreach ($note->getPhotos() as $photo) {

                $this->logger->debug('Uploading photo...', [
                    'note_id'          => $note->getId(),
                    'note_external_id' => $note->getExternalId(),
                    'city'             => $note->getCity()
                ]);

                $photo_id = $this->uploadPhoto($photo);

                $this->logger->debug('Uploading photo... done', [
                    'note_id'          => $note->getId(),
                    'note_external_id' => $note->getExternalId(),
                    'city'             => $note->getCity()
                ]);

                if (null === $photo_id) {

                    $this->logger->error('Uploading photo... nullable photo id', [
                        'note_id'          => $note->getId(),
                        'note_external_id' => $note->getExternalId(),
                        'city'             => $note->getCity()
                    ]);

                    continue;
                }

                $attachments[] = 'photo' . $this->user->getExternalId() . '_' . $photo_id;
            }

            if (empty($attachments)) {
                $this->logger->error('Can not upload attachments...', [
                    'note_id'          => $note->getId(),
                    'note_external_id' => $note->getExternalId(),
                    'city'             => $note->getCity()
                ]);

                return false;
            }

            $this->logger->debug('Publishing sleep...', [
                'note_id'          => $note->getId(),
                'note_external_id' => $note->getExternalId(),
                'city'             => $note->getCity()
            ]);

            usleep(500000);

            $this->logger->debug('Publishing sleep... done', [
                'note_id'          => $note->getId(),
                'note_external_id' => $note->getExternalId(),
                'city'             => $note->getCity()
            ]);

            $this->logger->debug('Publishing post...', [
                'note_id'          => $note->getId(),
                'note_external_id' => $note->getExternalId(),
                'city'             => $note->getCity()
            ]);

            $this->request->wallPost([
                'owner_id'    => '-' . $this->record->getGroupId(),
                'from_group'  => 1,
                'message'     => $message,
                'attachments' => implode(',', $attachments),
                'guid'        => $note->getId()
            ]);

            $this->logger->debug('Publishing post... done', [
                'note_id'          => $note->getId(),
                'note_external_id' => $note->getExternalId(),
                'city'             => $note->getCity()
            ]);

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return false;
        }

        return true;
    }
}

