<?php

namespace AppBundle\Model\Logic\Parser\Subway;

use AppBundle\Exception\ParseException;
use AppBundle\Model\Logic\Explorer\Subway\SubwayExplorerFactory;
use PHPHtmlParser\Dom;
use Schema\City\Subway;

class AvitoSubwayParser implements SubwayParserInterface
{
    /**
     * @var SubwayExplorerFactory
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
        if (!($data instanceof Dom)) {
            throw new ParseException(sprintf('%s: Data is not an instance of %s', __CLASS__ . '/' . __FUNCTION__, Dom::class));
        }

        $subways = $data->find('.avito-address-text');

        $subways_str = $subways[0];

        $explorer = $this->explorer_factory->init($city);

        return $explorer->explore($subways_str);
    }
}
