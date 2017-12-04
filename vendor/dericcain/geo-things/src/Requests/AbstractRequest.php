<?php

namespace GeoThing\Requests;

use GeoThing\Contracts\RequestContract;
use stdClass;

abstract class AbstractRequest implements RequestContract
{
    /**
     * @var array
     */
    protected $response;

    /**
     * @var string
     */
    protected $apiKey;

    protected $baseUrl = 'http://maps.googleapis.com/maps/api/';

    /**
     * Return the response.
     *
     * @return stdClass
     */
    public function receive()
    {
        $this->send();

        return $this->buildResponse();
    }

    /**
     * Make the request.
     *
     * @return void
     */
    public function send()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $this->response = json_decode(curl_exec($ch), true);
//        var_dump($this->response);
    }

    /**
     * @return mixed
     */
    protected function buildResponse()
    {
        if ($this->hasErrorOrNoResults()) {
            return $this->returnErrorResponse();
        }

        return $this->returnResults();
    }

    /**
     * Check to make sure the response has results and/or is not an error.
     *
     * @return bool
     */
    protected function hasErrorOrNoResults()
    {
        return $this->response['status'] != 'OK';
    }

    /**
     * @return bool
     */
    protected function hasApiKey()
    {
        return is_string($this->apiKey);
    }

}