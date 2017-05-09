<?php

namespace AppBundle\Model\Collector;

interface CollectorInterface
{
    /**
     * @param array $config
     * @param bool  $debug
     * @return array
     */
    public function collect(array $config, bool $debug = true): array;
}

