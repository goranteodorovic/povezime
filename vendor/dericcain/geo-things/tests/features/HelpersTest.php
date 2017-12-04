<?php

namespace features;

class HelpersTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    function the_handle_function_calls_the_handle_method_on_an_object()
    {
        $one = handle(new class {
            public function handle() {
                return 1;
            }
        });

        $this->assertEquals(1, $one);
    }

    /**
     * @test
     *
     * @expectedException        \Exception
     * @expectedExceptionMessage An object must be passed to the handle method. This is not what happened.
     */
    function the_handle_function_throws_exception_when_not_called_on_an_object()
    {
        $string = 'This is a string';
        handle($string);
    }

    /** @test */
    function the_get_address_helper_function_works_globally()
    {
        $lat = '33.5075002';
        $lng = '-86.8105789';

        $address = getAddress($lat, $lng);

        $this->assertEquals('1401', $address->street_number);
        $this->assertEquals('1st Avenue South', $address->street_name);
        $this->assertEquals('Birmingham', $address->city);
        $this->assertEquals('Alabama', $address->state);
        $this->assertEquals('35222', $address->zip);
        $this->assertEquals('1401 1st Ave S, Birmingham, AL 35222, USA', $address->formatted_address);
    }

    /** @test */
    function the_get_coordinates_helper_function_works_globally()
    {
        $address = '1401 1st Ave S';
        $zip = '35233';

        $coordinates = getCoordinates($address, $zip);

        $this->assertEquals('33.5075002', $coordinates->lat);
        $this->assertEquals('-86.8105789', $coordinates->lng);
    }

    /** @test */
    function the_get_distance_helper_function_works_globally()
    {
        $address1 = '1401 1st Ave S, Birmingham, AL 35233';
        $address2 = '1200 4th Ave N, Birmingham, AL 35203';

        $response = getDistance($address1, $address2);

        $this->assertLessThan(2, $response->distance);
    }
}
