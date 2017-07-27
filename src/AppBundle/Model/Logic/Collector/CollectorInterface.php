<?php

namespace AppBundle\Model\Logic\Collector;

use Schema\ParseList\Source;

interface CollectorInterface
{
    /**
     * @param Source $source
     * @return array
     */
    public function collect(Source $source);
}

