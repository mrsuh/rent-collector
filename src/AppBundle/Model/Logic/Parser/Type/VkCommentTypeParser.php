<?php

namespace AppBundle\Model\Logic\Parser\Type;

use AppBundle\Exception\ParseException;
use AppBundle\Model\Logic\Explorer\Tomita\Tomita;
use AppBundle\Model\Logic\Explorer\Tomita\TomitaExplorer;

class VkCommentTypeParser implements TypeParserInterface
{
    /**
     * @var TomitaExplorer
     */
    private $explorer;

    /**
     * VkCommentTypeParser constructor.
     * @param TomitaExplorer $explorer
     */
    public function __construct(TomitaExplorer $explorer)
    {
        $this->explorer = $explorer;
    }

    /**
     * @param $data
     * @return int
     * @throws ParseException
     */
    public function parse($data): int
    {
        if (!is_array($data)) {
            throw new ParseException(sprintf('%s: data is not an array', __CLASS__ . '\\' . __FUNCTION__));
        }

        if (!array_key_exists('text', $data)) {
            throw new ParseException('Key "text" is not exists in array');
        }

        $text = $data['text'];

        $response = $this->explorer->explore($text);

        if (!($response instanceof Tomita)) {
            throw new ParseException(sprintf('%s: response is not an instance of %s', __CLASS__ . '\\' . __FUNCTION__, Tomita::class));
        }

        return (int)$response->getType();
    }
}

