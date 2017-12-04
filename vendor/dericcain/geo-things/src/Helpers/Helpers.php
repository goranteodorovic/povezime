<?php

use GeoThing\Services\GetAddress;
use GeoThing\Services\GetCoordinates;
use GeoThing\Services\GetDistance;

if (!function_exists('handle')) {
    /**
     * @param $object
     * @return mixed
     * @throws Exception
     */
    function handle($object)
    {
        if (!is_object($object)) {
            throw new Exception('An object must be passed to the handle method. This is not what happened.');
        }

        return call_user_func([$object, 'handle']);
    }
}

if (!function_exists('getAddress')) {
    /**
     * @param $lat
     * @param $lng
     * @param bool $apiKey
     * @return mixed
     */
    function getAddress($lat, $lng, $apiKey = false)
    {
        return handle(new GetAddress($lat, $lng, $apiKey));
    }
}

if (!function_exists('getCoordinates')) {
    /**
     * @param $address
     * @param $zipCode
     * @param bool $apiKey
     * @return mixed
     */
    function getCoordinates($address, $zipCode, $apiKey = false)
    {
        return handle(new GetCoordinates($address, $zipCode, $apiKey));
    }
}

if (!function_exists('getDistance')) {
    /**
     * @param $origin
     * @param $destination
     * @param bool $apiKey
     * @return mixed
     */
    function getDistance($origin, $destination, $apiKey = false)
    {
        return handle(new GetDistance($origin, $destination, $apiKey));
    }
}