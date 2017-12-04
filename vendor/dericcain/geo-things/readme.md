#Geo Thing
[![Build Status](https://travis-ci.org/dericcain/geo-thing.svg?branch=master)](https://travis-ci.org/dericcain/geo-thing)

## Description
Ever need to quickly get the address from a set of coordinates? What about getting the coordinates from an address, or even the distance between 2 addresses? This is a very simple package that uses Google's API to perform those very operations. As a default, you do not have to supply an API key but you will be limited with how many API requests you can make. If you are not making a ton of calls, this should be good enough.

## Installation
Use composer to install the package like so:
```bash
composer require dericcain/geo-things
```

## Usage
It's fairly simple to use the package. You will need to import that package at the top of your PHP file. Once you have done that, you can use the different methods below.

#### Get Coordinates from Address
```php
// You will need to declare the namespace
use GeoThing/GeoThing;

$address = '123 Main Street';
$zip = '32119';

$results = GeoThing::getCoordinates($address, $zip);

$results->lat // 33.5075002
$results->lng // -86.8105789
$results->error // The error code from Google if there is one. This attribute will not be here if there is not error.
```
If there are no results, or there is an error, the object returned will have an `error` attribute giving the reason for the error. Also, the `lat` and `lng` attributes will be set to `null`.

#### Get Address from Coordinates
```php
// You will need to declare the namespace
use GeoThing/GeoThing;

$response = GeoThing::getAddress($lat, $lng);

$response->error // This will only be set if there is an error
$response->street_number // The number only
$response->street_name // The name of the street
$response->city // The full city name
$response->state // The full state name, not the abbreviation
$response->zip // The zip code
$response->formatted_address // The full formated address "277 Bedford Avenue, Brooklyn, NY 11211, USA"
```

#### Get Distance between Origin and Destination
```php
// You will need to declare the namespace
use GeoThing/GeoThing;

$response = GeoThing::getDistance($origin, $destination);

$response->error // This will only be set if there is an error
$response->distance // This will be a string like "1.2 mi" (I'll change this soon)
$response->duration // This will also be a string as of right now
```

#### Helper Functions
Once the package is installed using Composer, you will have access to some global helper functions. This is good if you want to call the different functions in a view, or somewhere else that is a little more difficult to declare `use` statements. Here are the helper functions:
```php
getAddress($lat, $lng, $apiKey); // $apiKey is optional
getCoordinates($address, $zip, $apiKey); // $apiKey is optional
getDistance($origin, $destination, $apiKey); // $apiKey is optional
```

## Contributing
Please feel free to help with this small project. Let me know if you see a bug, or want to add something. If you do a pull request, make sure that you test your code and all of the tests are passing. This is required before the work will be merged.

## Contact
Give me a shout!
- deric.cain@gmail.com
- @dericcain


## TODO
- [ ] Get distance between two sets of coordinates
- [ ] Add KM to distance as an option
