<?php

namespace AppBundle\Model\Logic\Parser\Subway;

use AppBundle\Exception\ParseException;
use AppBundle\Model\Logic\Explorer\Subway\SubwayExplorerFactory;
use AppBundle\Model\Logic\Explorer\Tomita\TomitaExplorer;
use Schema\City\Subway;

class VkCommentSubwayParser implements SubwayParserInterface
{
    /**
     * @var TomitaExplorer
     */
    private $explorer_factory;

    /**
     * VkCommentSubwayParser constructor.
     * @param SubwayExplorerFactory $explorer_factory
     */
    public function __construct(SubwayExplorerFactory $explorer_factory)
    {
        $this->explorer_factory = $explorer_factory;
    }

    /**
     * @param $data
     * @return Subway[]
     * @throws ParseException
     */
    public function parse($data, string $city)
    {
        if (!is_array($data)) {
            throw new ParseException(sprintf('%s: data is not an array', __CLASS__ . '\\' . __FUNCTION__));
        }

        if (!array_key_exists('text', $data)) {
            throw new ParseException('Key "text" is not exists in array');
        }

        $text = $data['text'];

        $explorer = $this->explorer_factory->init($city);

        return $explorer->explore($text);
    }
}
