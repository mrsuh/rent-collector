<?php

namespace AppBundle\Queue\Message;

use AppBundle\Model\Logic\Collector\RawData;
use Schema\Parse\Record\Source;

class NoteMessage
{
    /**
     * @var RawData
     */
    private $raw;

    /**
     * @var Source
     */
    private $source;

    /**
     * @return RawData
     */
    public function getRaw(): RawData
    {
        return $this->raw;
    }

    /**
     * @param RawData $raw
     * @return $this
     */
    public function setRaw(RawData $raw)
    {
        $this->raw = $raw;

        return $this;
    }

    /**
     * @return Source
     */
    public function getSource(): Source
    {
        return $this->source;
    }

    /**
     * @param Source $source
     * @return $this
     */
    public function setSource(Source $source)
    {
        $this->source = $source;

        return $this;
    }
}