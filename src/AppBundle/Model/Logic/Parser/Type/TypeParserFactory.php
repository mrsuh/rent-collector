<?php

namespace AppBundle\Model\Logic\Parser\Type;

use AppBundle\Exception\AppException;
use AppBundle\Model\Logic\Explorer\Tomita\TomitaExplorer;
use Schema\Parse\Record\Source;

class TypeParserFactory
{
    /**
     * @var TypeParserInterface[]
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
     * @return TypeParserInterface
     */
    public function init(string $type): TypeParserInterface
    {
        if (!array_key_exists($type, $this->instances)) {
            $this->instances[$type] = $this->getInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @param string $type
     * @return TypeParserInterface
     * @throws AppException
     */
    private function getInstance(string $type)
    {
        switch ($type) {
            case Source::TYPE_VK_COMMENT:
                return new VkCommentTypeParser($this->explorer);
                break;
            case Source::TYPE_VK_WALL:
                return new VkWallTypeParser($this->explorer);
                break;
            case Source::TYPE_AVITO:
                return new AvitoTypeParser();
                break;
            default:
                throw new AppException('Invalid type');
        }
    }
}

