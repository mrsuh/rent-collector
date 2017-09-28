<?php

namespace AppBundle\Model\Logic\Parser\DateTime;

use AppBundle\Exception\AppException;
use Schema\Parse\Record\Source;

class DateTimeParserFactory
{
    /**
     * @var DateTimeParserInterface[]
     */
    private $instances;

    /**
     * DateTimeParserFactory constructor.
     */
    public function __construct()
    {
        $this->instances = [];
    }

    /**
     * @param string $type
     * @return DateTimeParserInterface
     */
    public function init(string $type): DateTimeParserInterface
    {
        if (!array_key_exists($type, $this->instances)) {
            $this->instances[$type] = $this->getInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @param string $type
     * @return DateTimeParserInterface
     * @throws AppException
     */
    private function getInstance(string $type)
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentDateTimeParser();
                break;
            case Source::TYPE_VK_WALL:
                return new VkWallDateTimeParser();
                break;
            case Source::TYPE_AVITO:
                return new AvitoDateTimeParser();
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

