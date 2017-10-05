<?php

namespace AppBundle\Model\Logic\Filter\RawContent;

use AppBundle\Model\Logic\Collector\RawData;

interface RawContentFilterInterface
{
    /**
     * @param RawData $raw
     * @return bool
     */
    public function handle(RawData $raw);
}
