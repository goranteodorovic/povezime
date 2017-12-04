<?php

namespace GeoThing\Services;

use GeoThing\Contracts\ServicesContract;
use GeoThing\Requests\GetCoordinatesRequest;

class GetCoordinates implements ServicesContract
{
    /**
     * @var string
     */
    private $address;

    /**
     * @var string
     */
    private $zip;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @param $address
     * @param $zip
     * @param $apiKey
     */
    public function __construct($address, $zip, $apiKey)
    {
        $this->address = $address;
        $this->zip = $zip;
        $this->apiKey = $apiKey;
    }

    /**
     * @return \stdClass
     */
    public function handle()
    {
        $request = new GetCoordinatesRequest($this->address, $this->zip, $this->apiKey);

        return $request->receive();
    }
}