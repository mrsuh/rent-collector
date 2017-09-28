<?php

namespace AppBundle\Model\Logic\Parser\Link;

use Schema\Parse\Record\Source;

interface LinkParserInterface
{
    /**
     * @param Source $source
     * @param string $id
     * @return string
     */
    public function parse(Source $source, string $id): string;
}
