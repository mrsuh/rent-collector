<?php

namespace App\Explorer\Tomita;

use Schema\Note\Note;

class Tomita
{
    private $type  = Note::TYPE_ERR;
    private $price = -1.0;

    public function __construct(int $type, float $price)
    {
        $this->type  = $type;
        $this->price = $price;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}

