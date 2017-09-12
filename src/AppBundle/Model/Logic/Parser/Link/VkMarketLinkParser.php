<?php

namespace AppBundle\Model\Logic\Parser\Link;

use AppBundle\Exception\ParseException;
use Schema\Parse\Record\Source;

class VkMarketLinkParser implements LinkParserInterface
{
    /**
     * @param Source $source
     * @param string $id
     * @return string
     */
    public function parse(Source $source, string $id)
    {
        return $source->getLink() . '?id=' . $id;
    }
}

