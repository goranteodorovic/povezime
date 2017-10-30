<?php

namespace App\Controllers\Rides;

require_once dirname(__FILE__) . '/../../functions.php';

use App\Controllers\Controller;

use App\Models\User;
use App\Models\Reg;
use App\Models\Car;
use App\Models\Offer;
use App\Models\Search;
use App\Models\RideRequest;

Class OfferController extends Controller {

	public function rideOffer($request, $response){
	// 	required: user_id, route, car_id, seats, date, time, luggage
		$params = $request->getParams();
		$required = ['user_id', 'route', 'car_id', 'seats', 'date', 'time', 'luggage'];
		checkRequiredFields($required, $params);

		$params['seats_start'] = $params['seats'];
		$response = ['success' => 1];

		$route_array = explode(' - ', $params['route']);
		$params['description'] = getCityName($route_array[0]).' - '.getCityName(end($route_array));

		// insert offer into database
		$offer = Offer::create($params);
		if(!$offer->id)
			displayMessage('Spremanje ponude neuspješno.');
		unset($offer->created_at, $offer->updated_at);

		// check for searches
		$searches = $this->getSearchMatches($offer);
		if (!$searches)
			displayMessage('Nema podudaranja u potražnji', 1);

		// notification to searches
		$title = 'Ponuda prevoza';
		$user = User::fullName($offer->user_id);
		$message = $user.' je objavio da nudi prevoz, koji se podudara sa vašom potražnjom.';
		$fb_response = sendNotifications($title, $message, $searches['regs'], $offer, 'offer');
		$response['firebase'] = $fb_response;

		// response to offerer
		$response['regs'] = $searches['regs'];
		$response['searches'] = $searches['searches'];

		echo json_encode($response, JSON_UNESCAPED_UNICODE);

		/*
		if success
			success: 1
			if no matches => message
			regs
			searches (array of obj)
			firebase
		else
			success: 0
			message...

		*/
	}

	public function cancelOffer($request, $response){
	// 	required: id
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		// get all objects to work with
		$offer = offer::find($params['id']);
		if (!$offer)
			displayMessage('Pogrešan id...');
		
		$user = User::fullName($offer->user_id);
		$ride_requests = RideRequest::getAllByUserId($offer->user_id);

		$response['success'] = 1;
		$search_regs = array();

		foreach ($ride_requests as $rideRequest) {
			$delete_request = $rideRequest->deleteRequest();

			if (isset($delete_request['regs']))
				$search_regs = array_merge($search_regs, $delete_request['regs']);
		}

		$offer->deleteRecord();

		if (count($search_regs) > 0) {
			// notifications to searchers
			$title = 'Brisanje ponude prevoza';
			$message = $user.' je obrisao prevoz. Potražite novi!';
			$fb_response = sendNotifications($title, $message, $search_regs, $offer, 'offer');
			$response['firebase'] = $fb_response;
		}	

		echo json_encode($response, JSON_UNESCAPED_UNICODE);

		/*
		if success
			success: 1
			if reqs => firebase
		else
			success: 0
			message...
		*/
	}

	// 30.10.
	public function updateOffer($request, $response){
	//  required: id
	//  optional: user_id, route, car_id, seats, date, time, luggage
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		// get all objects/array of objects to work with
		$offer = offer::find($params['id']);
		if (!$offer)
			displayMessage('Pogrešan id...');

		if (isset($params['route'])) {
			$route_array = explode(' - ', $params['route']);
			$params['description'] = getCityName($route_array[0]).' - '.getCityName(end($route_array));
		}

		$user = User::fullName($offer->user_id);
		$ride_requests = RideRequest::where('offer_id', $offer->id)->where('user_id', $offer->user_id)->get();

		$response = ['success' => 1];
		$search_regs_for_deleted_requests = array();
		
		foreach ($ride_requests as $rideRequest) {
			$delete_request = $rideRequest->deleteRequest();

			if (isset($delete_request['regs']))
				$search_regs_for_deleted_requests = array_merge($$search_regs_for_deleted_requests, $delete_request['regs']);
		}

		if (!empty($search_regs_for_deleted_requests)) {
			$title = 'Izmjena ponude prevoza';
			$message = $user.' je izmijenio ponudu prevoza. Provjerite da li vam isti odgovara ili potražite novi!';
			$fb_response = sendNotifications($title, $message, $search_regs_for_deleted_requests, $offer, 'offer');
			$response['firebase']['delete'] = $fb_response;
		}

		// update offer
		if (isset($params['seats']))
			$params['seats_start'] = $params['seats'];

		$offer->updateRecord($params);

		// check matched searches
		$searches = $this->getSearchMatches($offer);
		if (!$searches)
			displayMessage('Nema podudaranja u potražnji', 1);

		// notification to searches
		$title = 'Izmjena ponude prevoza';
		$message = $user.' je objavio da nudi prevoz, koji se podudara sa vašom potražnjom.';
		$fb_response = sendNotifications($title, $message, $searches['regs'], $offer, 'offer');
		$response['firebase']['update'] = $fb_response;

		// response to offerer
		$response['searches'] = $searches['searches'];
		echo json_encode($response, JSON_UNESCAPED_UNICODE);

		/*
		if success
			success: 1
			if search matches 	=> searches
								=> firebase['update']
			if deleted requests => firebase['delete']
		*/
	}


	public function getSearchMatches($offer){
		$response = ['success' => 1];

		$searches = Search::getMatches($offer);
		$regs = array();

		foreach ($searches as $search) {
			$search->user = User::fullName($search->user_id);
			$search->from = getCityName($search->from);
			$search->to = getCityName($search->to);
			$search->date = date('d.M.Y.', strtotime($search->date));

			$response['searches'][] = $search;

			// get regs for notifications
			$regs = array_merge($regs, Reg::where('user_id', $search->id)->pluck('reg_id')->all());
		}

		if (empty($regs)) {
			return false;
		} else {
 			$response['regs'] = $regs;
			return $response;
 		}
	}
}