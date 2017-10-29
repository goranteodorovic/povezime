<?php

namespace App\Controllers\Rides;

require_once dirname(__FILE__) . '/../../functions.php';

use App\Controllers\Controller;

use App\Models\User;
use App\Models\Reg;
use App\Models\Car;
use App\Models\Offer;
use App\Models\Search;

Class OfferController extends Controller {

	public function rideOffer($request, $response){
	// 	required: user_id, route, car_id, seats, date, time, luggage
		$params = $request->getParams();
		$required = ['user_id', 'route', 'car_id', 'seats', 'date', 'time', 'luggage'];
		checkRequiredFields($required, $params);

		$params['seats_start'] = $params['seats'];
		$response = array();

		// insert offer into database
		$offer = Offer::create($params);
		if(!$offer->id)
			displayMessage('Spremanje ponude neuspješno.');
		unset($offer->created_at, $offer->updated_at);

		// check for searches
		$offer_route_arr = explode(" - ", $offer->route);

		$plus_one = date('Y-m-d', strtotime("+1 day", strtotime($offer->date)));
		$minus_one = date('Y-m-d', strtotime("-1 day", strtotime($offer->date)));

		// filter by seats, partly-date
		$searches = Search::select('id', 'user_id', 'from', 'to', 'date', 'one_day', 'seats', 'luggage')
		->where('seats', '>', 0)
		->where('seats', '<=', $offer->seats)
		->whereBetween('date', [$minus_one, $plus_one]);
		if($offer->luggage == 0){ $searches = $searches->where('luggage', 0); }
		$searches = $searches->get();

		$search_regs = array();		// regs to send  notifications to

		foreach ($searches as $index => $search) {
			if ($search->one_day == 0 && $offer->date != $search->date)
				unset($searches[$index]);

			// lat long of search object
			$from_lat = substr($search->from, 0, strpos($search->from, ','));
			$from_long = substr($search->from, strpos($search->from, ',')+1);

			$to_lat = substr($search->to, 0, strpos($search->to, ','));
			$to_long = substr($search->to, strpos($search->to, ',')+1);

			$from = null;		// closest place from SEARCH->FROM to offer route array
            $to = null;			// closest place from SEARCH->TO to offer route array

            // get distances between each latlong and offer from/to latlong
			foreach ($offer_route_arr as $latlong) {
				$lat = substr($latlong, 0, strpos($latlong, ','));
				$long = substr($latlong, strpos($latlong, ',')+1);

				$distance_from = getDistance($from_lat, $from_long, $lat, $long);
				$distance_to = getDistance($to_lat, $to_long, $lat, $long);

				if($distance_from < 5)	{	$from = $latlong;	}
				if($distance_to < 5)	{	$to = $latlong;		}
			}

			// filter result on saved latlong and direction
			if(!isset($from) || !isset($to) || strpos($offer->route, $from) > strpos($offer->route, $to)){
				unset($searches[$index]);
			} else {
				// set data to return
				$user = User::select('id', 'name', 'surname', 'email', 'phone')
				->where('id', $search->user_id)->first();
				$response_search['user'] = $user;

				unset($search->user_id);
				$search->from = getCityName($search->from);
				$search->to = getCityName($search->to);
				$response_search['route'] = $search;
				// get regs for notifications
				$search_regs = array_merge($search_regs, Reg::where('user_id', $user->id)->pluck('reg_id')->all());

				$response['searches'][] = $response_search;
			}
		}

		if (count($searches) == 0 ) {
			$response['message'] = 'Nema podudaranja u potražnji';
			exit(json_encode($response, JSON_UNESCAPED_UNICODE));
		}

		// notifications to searchers
		$title = 'Ponuda prevoza';
		$message = User::fullName($offer->user_id).' je objavio da nudi prevoz, koji se podudara sa vašom potražnjom.';
		$fb_response = sendNotifications($title, $message, $search_regs, $offer, 'offer');
		$response['firebase'] = $fb_response;

		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}

	public function cancelOffer($request, $response){
		//
	}

	public function updateOffer($request, $response){
		//
	}
}