<?php

namespace AppBundle\Model\Logic\Parser\Link;

use AppBundle\Exception\ParseException;
use Schema\Parse\Record\Source;

class VkCommentLinkParser implements LinkParserInterface
{
    /**
     * @param Source $source
     * @param string $id
     * @return string
     * @throws ParseException
     */
    public function parse(Source $source, string $id): string
    {
        //https://vk.com/topic-40633321_31885617?post=64442
        return $source->getLink() . '?post=' . $id;
    }
}

