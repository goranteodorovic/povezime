<?php

namespace GeoThing\Services;

use GeoThing\Contracts\ServicesContract;
use GeoThing\Requests\GetAddressRequest;

class GetAddress implements ServicesContract
{
    /**
     * @var string
     */
    private $lat;

    /**
     * @var string
     */
    private $lng;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @param $lat
     * @param $lng
     * @param $apiKey
     */
    public function __construct($lat, $lng, $apiKey)
    {
        $this->lat = $lat;
        $this->lng = $lng;
        $this->apiKey = $apiKey;
    }

    /**
     * @return \stdClass
     */
    public function handle()
    {
        $request = new GetAddressRequest($this->lat, $this->lng, $this->apiKey);

        return $request->receive();
    }
}