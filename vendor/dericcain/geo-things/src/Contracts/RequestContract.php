<?php

namespace GeoThing\Contracts;

interface RequestContract
{
    /**
     * This is where we will send the request.
     *
     * @return mixed
     */
    public function send();

    /**
     * This is where we will return the response object.
     *
     * @return \stdClass
     */
    public function receive();
}