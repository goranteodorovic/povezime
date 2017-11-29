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

	public function offerRide($request, $response){
	// 	required: user_id, route, car_id, seats, date, time, luggage
		$params = $request->getParams();
		$required = ['user_id', 'route', 'car_id', 'seats', 'date', 'time', 'luggage'];
		checkRequiredFields($required, $params);
		
		$route_array = explode(' - ', $params['route']);
		$params['seats_start'] = $params['seats'];
		$params['description'] = getCityName($route_array[0]).' - '.getCityName(end($route_array));

		// insert offer into database
		$offer = Offer::create($params);
		if(!$offer->id)
			displayMessage('Spremanje ponude neuspješno.', 503);

		// check for searches
		$searches = $this->getSearchMatches($offer);
		if (!$searches) {
            $resp['searches'] = '';
            exit(json_encode($resp));
        }

		// notification to searches
		$title = 'Ponuda prevoza';
		$user = User::fullName($offer->user_id);
		$message = $user.' je objavio da nudi prevoz, koji se podudara sa vašom potražnjom.';
		$fb_response = sendNotifications($title, $message, $searches['regs'], $offer, 'offer');
        $resp['firebase'] = $fb_response;

		// response to offerer
        $resp['regs'] = $searches['regs'];
        $resp['searches'] = $searches['searches'];

		echo json_encode($resp, JSON_UNESCAPED_UNICODE);

		/*
		if success
			if no matches => ''
			regs
			searches (array of obj)
			firebase
		else
			message...
		*/
	}

	public function offerRideCancel($request, $response){
	// 	required: id
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		// get all objects to work with
		$offer = offer::find($params['id']);
		if (!$offer)
			displayMessage('Pogrešan id.', 403);
		
		$user = User::fullName($offer->user_id);
		$ride_requests = RideRequest::where('offer_id', $offer->id)->where('user_id', $offer->user_id)->get();

		$search_regs = array();

		// check / delete related requests
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
            $resp['firebase'] = $fb_response;
		}	

		echo json_encode($resp, JSON_UNESCAPED_UNICODE);

		/*
		if success
			if reqs => firebase
		else
			message...
		*/
	}

	public function offerRideUpdate($request, $response){
	//  required: id
	//  optional: user_id, route, car_id, seats, date, time, luggage
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		// get all objects/array of objects to work with
		$offer = offer::find($params['id']);
		if (!$offer)
			displayMessage('Pogrešan id.', 403);

		if (isset($params['seats']))
			$params['seats_start'] = $params['seats'];
		else
			$params['seats'] = $offer->seats_start;

		if (isset($params['route'])) {
			$route_array = explode(' - ', $params['route']);
			$params['description'] = getCityName($route_array[0]).' - '.getCityName(end($route_array));
		}

		$user = User::fullName($offer->user_id);
		$ride_requests = RideRequest::where('offer_id', $offer->id)->where('user_id', $offer->user_id)->get();

		$response = ['success' => 1];
		$search_regs_for_deleted_requests = array();
		
		// check / delete related requests
		foreach ($ride_requests as $rideRequest) {
			$delete_request = $rideRequest->deleteRequest();

			if (isset($delete_request['regs']))
				$search_regs_for_deleted_requests = array_merge($search_regs_for_deleted_requests, $delete_request['regs']);
		}

		// update record
		$beforeUpdate = clone $offer;
		$offer->updateRecord($params);

		// notification about deleted requests
		if (!empty($search_regs_for_deleted_requests)) {
			$title = 'Izmjena ponude prevoza';
			$message = $user.' je izmijenio ponudu prevoza "'.$beforeUpdate->description.'" od '.date('d.M.Y.', strtotime($beforeUpdate->date)).'. Vaš zahtjev za prevozom je obrisan!';
			$fb_response = sendNotifications($title, $message, $search_regs_for_deleted_requests, $offer, 'offer');
			$response['firebase']['delete'] = $fb_response;
		}

		// check matched searches
		$searches = $this->getSearchMatches($offer);
		if (!$searches) {
            $response['searches'] = '';
            exit(json_encode($response));
        }

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
			firebase['update']
			if search matches 	=> searches
			if deleted requests => firebase['delete']
		else
			message...
		*/
	}


	public function getSearchMatches($offer){
		$response = ['success' => 1];
		$searches = Search::getMatches($offer);
		$regs = array();

		foreach ($searches as $search) {
			$search->user = User::fullName($search->user_id);
			$search->date = date('d.M.Y.', strtotime($search->date));
			unset($search->from, $search->to);

			$response['searches'][] = $search;
			$regs = array_merge($regs, Reg::where('user_id', $search->user_id)->pluck('reg_id')->all());
		}

		if (empty($regs)) {
			return false;
		} else {
 			$response['regs'] = $regs;
			return $response;
 		}

 		/*
 		if regs
 		    response['regs']
 		else
 		    false
 		*/
	}
}