<?php

namespace AppBundle\Model\Logic\Parser;

use Schema\City\Subway;
use Schema\Note\Photo;

interface Parser
{
    public function contactId();

    public function contactName(string $id = '');

    public function timestamp();

    public function description();

    public function id();

    public function link(string $id = '');

    public function phones();

    /**
     * @return Photo[]
     */
    public function photos();

    public function price();

    /**
     * @return Subway[]
     */
    public function subways();

    public function type();
}
