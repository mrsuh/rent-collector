<?php

namespace AppBundle\Model\Parser\Description;

use AppBundle\Exception\ParseException;

class DmirDescriptionParser
{
    public function parse($html)
    {
        $dom = \Sunra\PhpSimple\HtmlDomParser::str_get_html($html);

        $description_elem = $dom->find('[itemprop="description"]');

        if (!array_key_exists(0, $description_elem)) {
            throw new ParseException('Description not found');
        }

        return $description_elem[0]->innertext;
    }
}

