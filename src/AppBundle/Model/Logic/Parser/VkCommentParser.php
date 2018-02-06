<?php

namespace AppBundle\Model\Logic\Parser;

use AppBundle\Exception\ParseException;
use AppBundle\Model\Logic\Explorer\Subway\SubwayExplorer;
use Schema\Note\Photo;
use AppBundle\Model\Logic\Explorer\User\VkUserExplorer;
use AppBundle\Model\Logic\Explorer\Tomita\Tomita;
use AppBundle\Model\Logic\Explorer\Tomita\TomitaExplorer;
use Schema\Parse\Record\Source;

class VkCommentParser implements Parser
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var Source
     */
    protected $source;

    /**
     * @var VkUserExplorer
     */
    private $explorer_user;

    /**
     * @var TomitaExplorer
     */
    private $explorer_tomita;

    /**
     * @var SubwayExplorer
     */
    private $explorer_subway;


    /**
     * VkCommentParser constructor.
     * @param                $data
     * @param Source         $source
     * @param TomitaExplorer $explorer_tomita
     * @param SubwayExplorer $explorer_subway
     * @param VkUserExplorer $explorer_user
     * @throws ParseException
     */
    public function __construct(
        $data,
        Source $source,
        TomitaExplorer $explorer_tomita,
        SubwayExplorer $explorer_subway,
        VkUserExplorer $explorer_user
    )
    {
        if (!is_array($data)) {
            throw new ParseException(sprintf('%s: data is not an array', __CLASS__ . '\\' . __FUNCTION__));
        }

        if (!array_key_exists('text', $data)) {
            throw new ParseException('Key "text" is not exists in array');
        }

        $this->data            = $data;
        $this->source          = $source;
        $this->explorer_tomita = $explorer_tomita;
        $this->explorer_subway = $explorer_subway;
        $this->explorer_user   = $explorer_user;
    }

    /**
     * @return string
     * @throws ParseException
     */
    public function contactId()
    {
        if (!array_key_exists('from_id', $this->data)) {
            throw new ParseException('Key "from_id" is not exists in array');
        }

        return (string)$this->data['from_id'];
    }

    public function contactName(string $id = '')
    {
        $response = $this->explorer_user->explore($id);

        return (string)$response->getName();
    }

    /**
     * @return int
     * @throws ParseException
     */
    public function timestamp()
    {
        if (!array_key_exists('date', $this->data)) {
            throw new ParseException('Key "date" is not exists in array');
        }

        return (int)$this->data['date'];
    }

    /**
     * @return mixed
     * @throws ParseException
     */
    public function description()
    {
        return $this->data['text'];
    }

    public function id()
    {
        if (!array_key_exists('id', $this->data)) {
            throw new ParseException('Key "id" is not exists in array');
        }

        return (string)$this->data['id'];
    }

    public function link(string $id = '')
    {
        //https://vk.com/topic-40633321_31885617?post=64442

        $id = str_replace($this->source->getId() . '-', '', $id);

        return $this->source->getLink() . '?post=' . $id;
    }

    public function phones()
    {

        $response = $this->explorer_tomita->explore($this->data['text']);

        if (!($response instanceof Tomita)) {
            throw new ParseException(sprintf('%s: response is not an instance of %s', __CLASS__ . '\\' . __FUNCTION__, Tomita::class));
        }

        return $response->getPhones();
    }

    public function photos()
    {
        $photos = [];

        if (!array_key_exists('attachments', $this->data)) {
            return $photos;
        }

        foreach ($this->data['attachments'] as $attachment) {
            if (!array_key_exists('photo', $attachment)) {
                continue;
            }

            $photo = $attachment['photo'];

            switch (true) {
                case array_key_exists('photo_604', $photo):
                    $low = $photo['photo_604'];
                    break;
                case array_key_exists('photo_130', $photo):
                    $low = $photo['photo_130'];
                    break;
                default:
                    $low = null;
            }

            switch (true) {
                case array_key_exists('photo_1280', $photo):
                    $high = $photo['photo_1280'];
                    break;
                case array_key_exists('photo_807', $photo):
                    $high = $photo['photo_807'];
                    break;
                case array_key_exists('photo_604', $photo):
                    $high = $photo['photo_604'];
                    break;
                default:
                    $high = null;
            }

            if (null === $low || null == $high) {
                continue;
            }

            $photos[] =
                (new Photo())
                    ->setHigh($high)
                    ->setLow($low);
        }

        return $photos;
    }

    public function price()
    {
        $response = $this->explorer_tomita->explore($this->data['text']);

        if (!($response instanceof Tomita)) {
            throw new ParseException(sprintf('%s: response is not an instance of %s', __CLASS__ . '\\' . __FUNCTION__, Tomita::class));
        }

        return (int)$response->getPrice();
    }

    public function subways()
    {
        return $this->explorer_subway->explore($this->data['text']);
    }

    public function type()
    {
        $response = $this->explorer_tomita->explore($this->data['text']);

        if (!($response instanceof Tomita)) {
            throw new ParseException(sprintf('%s: response is not an instance of %s', __CLASS__ . '\\' . __FUNCTION__, Tomita::class));
        }

        return (int)$response->getType();
    }
}
