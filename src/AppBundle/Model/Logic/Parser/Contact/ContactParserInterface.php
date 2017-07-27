<?php

namespace AppBundle\Model\Logic\Parser\Contact;

use Schema\Note\Contact;

interface ContactParserInterface
{
    /**
     * @param array $data
     * @return Contact
     */
    public function parse(array $data);
}
