<?php

namespace App\Controllers\Rides;

require_once dirname(__FILE__) . '/../../functions.php';

use App\Controllers\Controller;

use App\Models\User;
use App\Models\Reg;
use App\Models\Car;
use App\Models\Offer;
use App\Models\Search;

Class SearchController extends Controller {

	public function rideSearch($request, $response){
	//	required: user_id, from, to, seats, date, one_day, luggage
		$params = $request->getParams();
		$required = ['user_id', 'from', 'to', 'seats', 'one_day', 'luggage'];
		checkRequiredFields($required, $params);
		
		$params['seats_start'] = $params['seats'];
		$response = array();

		// insert search into database
		$search = Search::create($params);
		if(!$search->id)
			displayMessage('Spremanje potražnje neuspješno.');	

		// check for offers; first filter by seats
		$offers = Offer::select('id', 'user_id', 'route', 'date', 'time', 'seats', 'luggage')
		->where('seats', '>', 0)
		->where('seats', '>=', $search->seats);
		// filter by luggage
		if($search->luggage == 1){ $offers = $offers->where('luggage', 1); }
		// filter by date
		if($search->one_day == 0){ $offers = $offers->where('date', $search->date); } 
		else {
			$plus_one = date('Y-m-d', strtotime("+1 day", strtotime($search->date)));
			$minus_one = date('Y-m-d', strtotime("-1 day", strtotime($search->date)));
			$offers = $offers->whereBetween('date', [$minus_one, $plus_one]);
		}

		$offers = $offers->get(); // array of objects

		// lat long of search object
		$from_lat = substr($search->from, 0, strpos($search->from, ','));
		$from_long = substr($search->from, strpos($search->from, ',')+1);

		$to_lat = substr($search->to, 0, strpos($search->to, ','));
		$to_long = substr($search->to, strpos($search->to, ',')+1);

		$from = null;		// closest place from SEARCH->FROM to offer route array
		$to = null;			// closest place from SEARCH->TO to offer route array

		$offer_regs = array();	

		foreach($offers as $index => $offer){
			$offer_route_arr = explode(" - ", $offer->route);

			foreach($offer_route_arr as $latlong){
				$lat = substr($latlong, 0, strpos($latlong, ','));
				$long = substr($latlong, strpos($latlong, ',')+1);

				// get distances between each latlong and search from/to latlong
				// if distance is less than 5km save latlong for further check
				$distance_from = getDistance($from_lat, $from_long, $lat, $long);
				$distance_to = getDistance($to_lat, $to_long, $lat, $long);

				if($distance_from < 5)	{	$from = $latlong;	}
				if($distance_to < 5)	{	$to = $latlong;		}
			}

			// filter result on saved latlong and direction
			if(!isset($from) || !isset($to) || strpos($offer->route, $from) > strpos($offer->route, $to)){
				unset($offers[$index]);
			} else {
				// set data to return
				$user = User::select('id', 'name', 'surname', 'email', 'phone')
				->where('id', $offer->user_id)->first();
				$response_offer['user'] = $user;

				unset($offer->user_id, $offer->route);
				$offer->from = getCityName($offer_route_arr[0]);
				$offer->to = getCityName(end($offer_route_arr));
				$response_offer['route'] = $offer;
				// get regs for notifications
				$offer_regs = array_merge($offer_regs, Reg::where('user_id', $user->id)->pluck('reg_id')->all());

				$response['offers'][] = $response_offer;
			}
		}

		if(count($offers) == 0 ){
			$response['message'] = 'Nema podudaranja u ponudi';
			exit(json_encode($response, JSON_UNESCAPED_UNICODE));
		}

		// notifications to offerers
		$title = 'Potražnja prevoza';
		$message = User::fullName($search->user_id).' je objavio da traži prevoz, koji se podudara sa vašom ponudom.';
		$fb_response = sendNotifications($title, $message, $offer_regs, $search, 'search');
		$response['firebase'] = $fb_response;

		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}

	public function cancelSearch($request, $response){
		//
	}

	public function updateSearch($request, $response){
		//
	}
	
}