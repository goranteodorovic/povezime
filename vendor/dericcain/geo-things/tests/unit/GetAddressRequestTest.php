<?php

namespace unit;

use GeoThing\GeoThing;

class GetAddressRequestTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    function lat_and_lng_return_an_address_object()
    {
        $lat = '33.5072665';
        $lng = '-86.8104413';

        $address = GeoThing::getAddress($lat, $lng);

        $this->assertObjectHasAttribute('street_number', $address);
        $this->assertObjectHasAttribute('street_name', $address);
        $this->assertObjectHasAttribute('city', $address);
        $this->assertObjectHasAttribute('state', $address);
        $this->assertObjectHasAttribute('zip', $address);
        $this->assertObjectHasAttribute('formatted_address', $address);
    }

    /** @test */
    function bad_lat_and_lng_return_an_error_attribute()
    {
        $lat = '102934234.5072665';
        $lng = '-99999999.8104413';

        $address = GeoThing::getAddress($lat, $lng);

        $this->assertObjectHasAttribute('error', $address);
        $this->assertNull($address->street_number);
        $this->assertNull($address->street_name);
        $this->assertNull($address->city);
        $this->assertNull($address->state);
        $this->assertNull($address->zip);
        $this->assertNull($address->formatted_address);
    }

    function an_error_is_returned_when_using_a_bad_api_key()
    {
        $lat = '33.5072665';
        $lng = '-86.8104413';

        $address = GeoThing::getAddress($lat, $lng, 'asdfaqewrasdfxvbdfghw56wertsdfxvcbsdfgw3');

        $this->assertObjectHasAttribute('error', $address);
    }
}
