<?php

namespace GeoThing\Requests;

use stdClass;

class GetDistanceRequest extends AbstractRequest
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
     * @param $origin
     * @param $destination
     */
    public function __construct($origin, $destination)
    {
        $this->origin = $origin;
        $this->destination = $destination;
    }

    /**
     * We need to build the URL string.
     *
     * @return string
     */
    protected function apiUrl()
    {
        $origin = str_replace(' ', '+', urlencode($this->origin));
        $destination = str_replace(' ', '+', urlencode($this->destination));

        if ($this->hasApiKey()) {
            var_dump($this->apiKey);
            return "{$this->baseUrl}distancematrix/json?units=imperial&origins={$origin}&destinations={$destination}&key={$this->apiKey}";
        }

        return "{$this->baseUrl}distancematrix/json?units=imperial&origins={$origin}&destinations={$destination}";
    }

    /**
     * When we have an error or no results, we will return that here.
     *
     * @return stdClass
     */
    protected function returnErrorResponse()
    {
        $response = new stdClass;
        $response->error = $this->response['status'];
        $response->distance = null;
        $response->duration = null;

        return $response;
    }

    /**
     * We will construct the results into an object and return it.
     *
     * @return stdClass
     */
    protected function returnResults()
    {
        $response = new stdClass;
        $response->distance = $this->convertMetersToMiles($this->response['rows'][0]['elements'][0]['distance']['value']) ?? null;
        $response->duration = $this->response['rows'][0]['elements'][0]['duration']['value'] ?? null;

        return $response;
    }

    /**
     * Google only gives us meters, so we need to convert them to miles.
     *
     * @param $distanceInMeters
     * @return mixed
     */
    private function convertMetersToMiles($distanceInMeters)
    {
        return $distanceInMeters * 0.000621371;
    }
}