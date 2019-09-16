<?php


namespace App\Collector;


class Result
{
    /** @var RawData[] */
    private $items;
    private $done;

    public function __construct(bool $done, array $items = [])
    {
        $this->done  = $done;
        $this->items = $items;
    }

    /**
     * @return RawData[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function isDone(): bool
    {
        return $this->done;
    }
}