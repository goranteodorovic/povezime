<?php

use GeoThing\GeoThing;


class GetAddressTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    function lat_and_lng_are_returned_when_address_and_zip_are_supplied()
    {
        $lat = '33.5075002';
        $lng = '-86.8105789';

        $address = GeoThing::getAddress($lat, $lng);

        $this->assertEquals('1401', $address->street_number);
        $this->assertEquals('1st Avenue South', $address->street_name);
        $this->assertEquals('Birmingham', $address->city);
        $this->assertEquals('Alabama', $address->state);
        $this->assertEquals('35222', $address->zip);
        $this->assertEquals('1401 1st Ave S, Birmingham, AL 35222, USA', $address->formatted_address);
    }
}
