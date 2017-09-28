<?php

namespace AppBundle\Model\Logic\Parser\Link;

use AppBundle\Exception\ParseException;
use Schema\Parse\Record\Source;

class VkWallLinkParser implements LinkParserInterface
{
    /**
     * @param Source $source
     * @param string $id
     * @return string
     * @throws ParseException
     */
    public function parse(Source $source, string $id): string
    {
        $params = json_decode($source->getParameters(), true);

        if (!is_array($params)) {

            throw new ParseException('Source params has invalid json');
        }

        if (!array_key_exists('owner_id', $params)) {

            throw new ParseException('Source params has not key "owner_id"');
        }

        //https://vk.com/fungroup?w=wall-57466174_309390

        return $source->getLink() . '?w=wall' . $params['owner_id'] . '_' . $id;
    }
}

