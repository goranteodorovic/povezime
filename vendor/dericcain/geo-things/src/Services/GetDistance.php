<?php

namespace GeoThing\Services;

use GeoThing\Contracts\ServicesContract;
use GeoThing\Requests\GetDistanceRequest;

class GetDistance implements ServicesContract
{

    /**
     * @var string
     */
    private $origin;

    /**
     * @var string
     */
    private $destination;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @param $origin
     * @param $destination
     * @param $apiKey
     */
    public function __construct($origin, $destination, $apiKey)
    {
        $this->origin = $origin;
        $this->destination = $destination;
        $this->apiKey = $apiKey;
    }

    /**
     * @return \stdClass
     */
    public function handle()
    {
        $request = new GetDistanceRequest($this->origin, $this->destination, $this->apiKey);

        return $request->receive();
    }
}