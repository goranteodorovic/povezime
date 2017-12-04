<?php

use GeoThing\Requests\GetDistanceRequest;

class GetDistanceRequestTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    function a_request_made_to_get_distance_should_have_a_distance_and_duration_attribute()
    {
        $origin = '1401 1st Ave S, Birmingham, AL 35233';
        $destination = '1200 4th Ave N, Birmingham, AL 35203';
        $request = new GetDistanceRequest($origin, $destination);

        $this->assertInstanceOf(stdClass::class, $request->receive());
        $this->assertObjectHasAttribute('distance', $request->receive());
        $this->assertObjectHasAttribute('duration', $request->receive());
        $this->assertObjectNotHasAttribute('error', $request->receive());
    }

    /** @test */
    function an_error_attribute_is_returned_when_there_are_no_results()
    {
        $origin = '';
        $destination = '';
        $request = new GetDistanceRequest($origin, $destination);

        $this->assertInstanceOf(stdClass::class, $request->receive());
        $this->assertObjectHasAttribute('error', $request->receive());
    }

    function an_error_is_returned_when_using_a_bad_api_key()
    {
        $origin = '1401 1st Ave S, Birmingham, AL 35233';
        $destination = '1200 4th Ave N, Birmingham, AL 35203';

        $request = new GetDistanceRequest($origin, $destination, 'asdfaqewrasdfxvbdfghw56wertsdfxvcbsdfgw3');

        $this->assertObjectHasAttribute('error', $request->receive());
    }
}
