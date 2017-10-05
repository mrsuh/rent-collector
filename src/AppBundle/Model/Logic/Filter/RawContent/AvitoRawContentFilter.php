<?php

namespace AppBundle\Model\Logic\Filter\RawContent;

use AppBundle\Model\Logic\Collector\RawData;
use PHPHtmlParser\Dom;

class AvitoRawContentFilter implements RawContentFilterInterface
{
    /**
     * @param RawData $raw
     * @return bool
     */
    public function handle(RawData $raw)
    {
        $dom = new Dom();

        $content = $raw->getContent();

        $raw->setContent($dom->load($content));

        return true;
    }
}
