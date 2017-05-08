<?php

namespace AppBundle\Model\Parser\Contact;

interface ContactParserInterface
{
    /**
     * @param array $data
     * @return Contact
     */
    public function parse(array $data): Contact;
}
