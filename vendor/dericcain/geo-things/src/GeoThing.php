<?php

namespace GeoThing;

use GeoThing\Services\GetAddress;
use GeoThing\Services\GetCoordinates;
use GeoThing\Services\GetDistance;

class GeoThing
{

    /**
     * Takes a set of coordinates and returns the address.
     *
     * @param $lat
     * @param $lng
     * @param $apiKey
     * @return \stdClass
     */
    public static function getAddress($lat, $lng, $apiKey = false)
    {
        return handle(new GetAddress($lat, $lng, $apiKey));
    }

    /**
     * Takes an address and zip code and returns lat and lng.
     *
     * @param $address
     * @param $zipCode
     * @param bool $apiKey
     * @return \stdClass
     */
    public static function getCoordinates($address, $zipCode, $apiKey = false)
    {
        return handle(new GetCoordinates($address, $zipCode, $apiKey));
    }

    /**
     * Gets the distance between 2 addresses.
     *
     * @param $origin
     * @param $destination
     * @param bool $apiKey
     * @return \stdClass
     */
    public static function getDistance($origin, $destination, $apiKey = false)
    {
        return handle(new GetDistance($origin, $destination, $apiKey));
    }
}