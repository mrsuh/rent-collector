<?php

namespace AppBundle\Model\Logic\Explorer\Subway;

use AppBundle\Model\Document\City\SubwayModel;
use Schema\Parse\Record\Source;

class SubwayExplorerFactory
{
    /**
     * @var SubwayExplorer[]
     */
    private $instances;

    /**
     * @var SubwayModel
     */
    private $model_subway;

    /**
     * SubwayExplorer constructor.
     * @param SubwayModel $model_subway
     */
    public function __construct(SubwayModel $model_subway)
    {
        $this->model_subway = $model_subway;
        $this->instances    = [];
    }

    /**
     * @param Source $source
     * @return SubwayExplorer
     */
    public function init(Source $source)
    {
        $city = $source->getCity();

        if (!array_key_exists($city, $this->instances)) {
            $subways = $this->model_subway->findByCity($city);

            $this->instances[$city] = new SubwayExplorer($subways);
        }

        return $this->instances[$city];
    }
}

