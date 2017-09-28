<?php

namespace AppBundle\Model\Logic\Collector;

class AvitoConfig
{
    /**
     * @var bool
     */
    private $finish;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var int
     */
    private $page;

    public function __toString()
    {
        return json_encode(['timestamp' => $this->timestamp, 'page' => $this->page, 'finish' => $this->finish]);
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
    public function getTimestamp(): int
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

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return $this
     */
    public function setPage(int $page)
    {
        $this->page = $page;

        return $this;
    }
}
