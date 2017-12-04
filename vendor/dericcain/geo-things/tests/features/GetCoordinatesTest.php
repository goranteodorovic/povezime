<?php

use GeoThing\GeoThing;

class GetCoordinatesTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    function lat_and_lng_are_returned_when_address_and_zip_are_supplied()
    {
        // 33.5072665,-86.8104413 as retrieved from Google Maps
        $address = '1401 1st Ave S';
        $zip = '35233';

        $coordinates = GeoThing::getCoordinates($address, $zip);

        $this->assertEquals('33.5075002', $coordinates->lat);
        $this->assertEquals('-86.8105789', $coordinates->lng);
    }

}
