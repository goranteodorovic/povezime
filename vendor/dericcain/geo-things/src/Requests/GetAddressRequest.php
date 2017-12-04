<?php

namespace GeoThing\Requests;

use stdClass;

class GetAddressRequest extends AbstractRequest
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
     * When we have an error or no results, we will return that here.
     *
     * @return stdClass
     */
    protected function returnErrorResponse()
    {
        $response = new stdClass;
        $response->error = $this->response['status'];
        $response->street_number = null;
        $response->street_name = null;
        $response->city = null;
        $response->state = null;
        $response->zip = null;
        $response->formatted_address = null;

        return $response;
    }

    /**
     * We need to build the URL string.
     *
     * @return string
     */
    protected function apiUrl()
    {
        $encodedQuery = urlencode($this->lat . ',' . $this->lng);

        if ($this->hasApiKey()) {
            return "{$this->baseUrl}geocode/json?latlng={$encodedQuery}&key={$this->apiKey}";
        }

        return "{$this->baseUrl}geocode/json?latlng={$encodedQuery}";
    }

    /**
     * We will construct the results into an object and return it.
     *
     * @return stdClass
     */
    protected function returnResults()
    {
        $response = new stdClass;
        $response->street_number = $this->response['results'][0]['address_components'][0]['long_name'] ?? null;
        $response->street_name = $this->response['results'][0]['address_components'][1]['long_name'] ?? null;
        $response->city = $this->response['results'][0]['address_components'][3]['long_name'] ?? null;
        $response->state = $this->response['results'][0]['address_components'][5]['long_name'] ?? null;
        $response->zip = $this->response['results'][0]['address_components'][7]['long_name'] ?? null;
        $response->formatted_address = $this->response['results'][0]['formatted_address'] ?? null;

        return $response;
    }
}