<?php

namespace App\Document\Parse\Record;

use Schema\Parse\Record\Record;

class SourceModel
{
    /**
     * @param Record $record
     * @param        $source_id
     * @return null|\Schema\Parse\Record\Source
     */
    public function findOneById(Record $record, $source_id)
    {
        foreach ($record->getSources() as $source) {
            if ($source->getId() === $source_id) {
                return $source;
            }
        }

        return null;
    }
}
