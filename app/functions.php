<?php

use App\Models\User;
use App\Models\Car;

function checkRequiredFields($required, $params){
	foreach($required as $field){
		if(!isset($params[$field])){
            $msg = 'Provjeriti neophodna polja: '.implode(', ', $required).'.';
            displayMessage($msg, 400);
		}
	}
}

function displayMessage($msg, $response_code = 200){
    if (isset($response_code) && $response_code != 200) {
        http_response_code($response_code);
    }
    $resp['message'] = $msg;
    exit(json_encode($resp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

/* distance from point to point in km */
function getDistanceGeoKit($from, $to){
    $math = new Geokit\Math();
    $distance = $math->distanceHaversine($from, $to);
    if($distance->kilometers() != null)
        return $distance->kilometers();
    else
        return 0.001;
}

/* firebase notification */
function sendNotifications($title, $message, $reg_ids, $object, $type){
	// configure payload data

    if (isset($object->user_id))
        $user_id = $object->user_id;
    else if (isset($object->user->id))
        $user_id = $object->user->id;
    else
        displayMessage('Greška u firebase notifikaciji, nedostaje user_id...');

	$route = clone $object;
	if (strpos($route->date, '-'))
	    $route->date = date('d.M.Y.', strtotime($object->date));

	unset($route->user_id, $route->seats_start, $route->created_at, $route->updated_at);

	if ($type == 'offer'){
        if (strlen($route->time) > 6)
            $route->time = substr($object->time, 0, 5);

		unset($route->route, $route->car_id);

		$car = Car::select('make', 'model')->where('id', $user_id)->first();
		$route->car = $car->make.' '.$car->model;
	} else
		unset($route->from, $route->to);

	// SEND NOTIFICATION
	$firebase = new Firebase();
	$push = new Push();
	
	$push->setTitle($title);
	$push->setMessage($message);
	$push->setImage('');
	$push->setIsBackground(FALSE);
	$push->setPayload($route);

	$json = $push->getPush();
	$reg_ids = json_encode(array_unique($reg_ids));
	$fb_response = $firebase->sendMultiple($reg_ids, $json);

	//return $fb_response;
    $resp = ['payload' => $route, 'response' => $fb_response];
	return $resp;
}

function curlGetRequest($url){
	// Get cURL resource
	$curl = curl_init();
	// Set some options - we are passing in a useragent too here
	curl_setopt_array($curl, array(
	    CURLOPT_RETURNTRANSFER => 1,
	    CURLOPT_SSL_VERIFYPEER => false,
	    CURLOPT_URL => $url,
	    CURLOPT_USERAGENT => 'Codular Sample cURL Request'
	));
	// Send the request & save response to $resp
	$resp = curl_exec($curl);
	// Error handling
	if(!curl_exec($curl))
	    die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));

	// Close request to clear up some resources
	curl_close($curl);
	return $resp;
}

/*function getCityName($latlng){
    $cityName = '';
    $api = 'AIzaSyDDvwpoPhOSNSn4W54Qq_XUxHpSbvOOpMc';
    $string = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.$latlng.'&key='.$api;
    $jsonObj = json_decode(curlGetRequest($string));
    foreach($jsonObj->results as $result){
        $types = $result->address_components[0]->types;

        if($types[0] == 'locality' && $types[1] == 'political'){
            $cityName = $result->address_components[0]->long_name;
            break;
        }
    }

    return !empty($cityName) ? $cityName : getCityName($latlng);
}*/