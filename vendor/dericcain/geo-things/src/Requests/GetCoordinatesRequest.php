<?php

namespace GeoThing\Requests;

use stdClass;

class GetCoordinatesRequest extends AbstractRequest
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
     * @param $address
     * @param $zip
     */
    public function __construct($address, $zip)
    {
        $this->address = $address;
        $this->zip = $zip;
    }

    /**
     * We need to build the URL string.
     *
     * @return string
     */
    protected function apiUrl()
    {
        $encodedQuery = str_replace(' ', '+', urlencode($this->address . ' ' . $this->zip));

        if ($this->hasApiKey()) {
            return "{$this->baseUrl}geocode/json?address={$encodedQuery}&key={$this->apiKey}";
        }

        return "{$this->baseUrl}geocode/json?address={$encodedQuery}";
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
        $response->lat = null;
        $response->lng = null;

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
        $response->lat = $this->response['results'][0]['geometry']['location']['lat'] ?? null;
        $response->lng = $this->response['results'][0]['geometry']['location']['lng'] ?? null;

        return $response;
    }
}