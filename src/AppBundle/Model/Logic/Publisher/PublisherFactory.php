<?php

namespace AppBundle\Model\Logic\Publisher;

use AppBundle\Exception\PublishException;
use AppBundle\Model\Document\City\SubwayModel;
use AppBundle\Model\Document\Note\NoteModel;
use AppBundle\Model\Document\Publish\User\UserModel;
use AppBundle\Request\Client;
use AppBundle\Request\VkPrivateRequest;
use Monolog\Logger;
use Schema\Publish\Record\Record;

class PublisherFactory
{
    /**
     * @var PublisherInterface[]
     */
    private $instances;

    /**
     * @var NoteFormatter
     */
    private $formatter;

    private $model_subway;
    private $model_user;
    private $model_note;
    private $client;
    private $logger;

    /**
     * PublisherFactory constructor.
     * @param Client      $client
     * @param SubwayModel $model_subway
     * @param UserModel   $model_user
     * @param NoteModel   $model_note
     * @param Logger      $logger
     */
    public function __construct(
        Client $client,
        SubwayModel $model_subway,
        UserModel $model_user,
        NoteModel $model_note,
        Logger $logger
    )
    {
        $this->instances = [];

        $this->client       = $client;
        $this->model_subway = $model_subway;
        $this->model_user   = $model_user;
        $this->model_note   = $model_note;
        $this->logger       = $logger;
        $this->formatter    = new NoteFormatter($model_subway);
    }

    /**
     * @param Record $record
     * @return PublisherInterface
     */
    public function init(Record $record)
    {
        $city = $record->getCity();

        if (!array_key_exists($city, $this->instances)) {
            $this->instances[$city] = $this->getInstance($record);
        }

        return $this->instances[$city];
    }

    /**
     * @param Record $record
     * @return VkPublisher
     * @throws PublishException
     */
    private function getInstance(Record $record)
    {
        $user = $this->model_user->findOneByUsername($record->getUser());

        if (null === $user) {

            throw new PublishException('User with username "' . $record->getUser() . '" not found');
        }

        $request = new VkPrivateRequest($this->client, $user);

        return new VkPublisher($request, $this->formatter, $this->model_note, $record, $user, $this->logger);
    }
}