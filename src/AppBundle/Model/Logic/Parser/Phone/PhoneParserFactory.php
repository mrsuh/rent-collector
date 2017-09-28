<?php

namespace AppBundle\Model\Logic\Parser\Phone;

use AppBundle\Exception\AppException;
use AppBundle\Model\Logic\Explorer\Tomita\TomitaExplorer;
use Schema\Parse\Record\Source;

class PhoneParserFactory
{
    /**
     * @var PhoneParserInterface[]
     */
    private $instances;

    /**
     * @var TomitaExplorer
     */
    private $explorer;

    /**
     * TypeParserFactory constructor.
     * @param TomitaExplorer $explorer
     */
    public function __construct(TomitaExplorer $explorer)
    {
        $this->instances = [];
        $this->explorer  = $explorer;
    }

    /**
     * @param string $type
     * @return PhoneParserInterface
     */
    public function init(string $type): PhoneParserInterface
    {
        if (!array_key_exists($type, $this->instances)) {
            $this->instances[$type] = $this->getInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @param string $type
     * @return PhoneParserInterface
     * @throws AppException
     */
    private function getInstance(string $type)
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentPhoneParser($this->explorer);
                break;
            case Source::TYPE_VK_WALL:
                return new VkWallPhoneParser($this->explorer);
                break;
            case Source::TYPE_AVITO:
                return new AvitoPhoneParser();
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

