<?php

namespace AppBundle\Model\Logic\Collector;

use Schema\Parse\Record\Source;

interface CollectorInterface
{
    /**
     * @param Source $source
     * @return array
     */
    public function collect(Source $source);

    /**
     * @param RawData $data
     * @return RawData
     */
    public function handle(RawData $data);
}

