<?php

use App\Models\User;
use App\Models\Car;

function checkRequiredFields($required, $params){
	foreach($required as $field){
		if(!isset($params[$field])){
			$response['success'] = 0;
			$response['message'] = 'Provjerite neophodna polja: '.implode(', ', $required).'!';
			exit(json_encode($response, JSON_UNESCAPED_UNICODE));
		}
	}
}

function displayMessage($msg, $success = 0){
	$response = array();
	$response['success'] = $success;
	$response['message'] = $msg;
	exit(json_encode($response, JSON_UNESCAPED_UNICODE));
}

/* distance from point to point in km */
function getDistance($lat1, $lon1, $lat2, $lon2) {
	$rad = M_PI / 180;
	$distance = acos(sin($lat2*$rad) * sin($lat1*$rad) + cos($lat2*$rad) * cos($lat1*$rad) * cos($lon2*$rad - $lon1*$rad)) * 6371;
	return is_nan($distance) ? 0 : $distance;
}

/* firebase notification */
function sendNotifications($title, $message, $reg_ids, $object, $type){
	// configure payload data
	$route = clone $object;
	$route->date = date('d.M.Y.', strtotime($object->date));
	unset($route->user_id, $route->seats_start, $route->created_at, $route->updated_at);

	if($type == 'offer'){
		$route_array = explode(' - ', $object->route);
		$route->from = getCityName($route_array[0]);
		$route->to = getCityName(end($route_array));
		$route->time = substr($object->time, 0, 5);
		unset($route->route, $route->car_id);

		$car = Car::select('make', 'model')->where('id', $object->user_id)->first();
	} else {
		$route->from = getCityName($route->from);
		$route->to = getCityName($route->to);
	}

	// SEND NOTIFICATION
	$firebase = new Firebase();
	$push = new Push();
	
	$payload = new stdClass();
	$payload->user = User::fullName($object->user_id);
	if($type == 'offer'){ $payload->car = $car->make.' '.$car->model; }
	$payload->route = $route;
	//$payload = json_encode($payload, JSON_UNESCAPED_UNICODE);

	$push->setTitle($title);
	$push->setMessage($message);
	$push->setImage('');
	$push->setIsBackground(FALSE);
	$push->setPayload($payload);

	$json = $push->getPush();
	$reg_ids = json_encode(array_unique($reg_ids));
	$fb_response = $firebase->sendMultiple($reg_ids, $json);

	//return $fb_response;

	$response = ['payload' => $payload, 'response' => $fb_response];
	return $response;
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

function getCityName($latlng){
	$jsonObj = json_decode(curlGetRequest('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$latlng));
	foreach($jsonObj->results as $result){
		$types = $result->address_components[0]->types;

		if($types[0] == 'locality' && $types[1] == 'political')
			$cityName = $result->address_components[0]->long_name;
	}

	if (isset($cityName))
		return $cityName;
	else 
		getCityName($latlng);
}