<?php

namespace App\Collector;

use Schema\Parse\Record\Source;

interface CollectorInterface
{
    public function collect(Source $source, string $period): Result;
}

