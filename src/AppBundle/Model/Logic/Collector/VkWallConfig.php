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
}
