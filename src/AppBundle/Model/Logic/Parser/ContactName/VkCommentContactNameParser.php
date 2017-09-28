<?php

namespace AppBundle\Model\Logic\Parser\ContactName;

use AppBundle\Exception\ParseException;
use AppBundle\Model\Logic\Explorer\User\VkUserExplorer;

class VkCommentContactNameParser implements ContactNameParserInterface
{
    /**
     * @var VkUserExplorer
     */
    private $explorer;

    /**
     * VkCommentContactIdParser constructor.
     * @param VkUserExplorer $explorer
     */
    public function __construct(VkUserExplorer $explorer)
    {
        $this->explorer = $explorer;
    }

    /**
     * @param $data
     * @return string
     * @throws ParseException
     */
    public function parse($data): string
    {
        if (!is_string($data)) {
            throw new ParseException(sprintf('%s: data is not an array', __CLASS__ . '\\' . __FUNCTION__));
        }

        $response = $this->explorer->explore($data);

        return (string)$response->getName();
    }
}
