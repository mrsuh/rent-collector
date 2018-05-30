<?php

namespace AppBundle\Model\Logic\Collector;

class VkWallConfig
{
    /**
     * @var int
     */
    private $finish;

    /**
     * @var bool
     */
    private $offset;

    /**
     * @var int
     */
    private $timestamp;

    public function __toString()
    {
        return json_encode(['offset' => $this->offset, 'finish' => $this->finish]);
    }

    /**
     * @return mixed
     */
    public function isFinish()
    {
        return (bool)$this->finish;
    }

    /**
     * @param bool $finish
     * @return $this
     */
    public function setFinish(bool $finish)
    {
        $this->finish = $finish;

        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function setOffset(int $offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     * @return $this
     */
    public function setTimestamp(int $timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}
