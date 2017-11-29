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

Class SearchController extends Controller {

	public function searchRide($request, $response){
	//	required: user_id, from, to, seats, date, one_day, luggage
		$params = $request->getParams();
		$required = ['user_id', 'from', 'to', 'seats', 'one_day', 'luggage'];
		checkRequiredFields($required, $params);
		
		$params['seats_start'] = $params['seats'];
		$params['description'] = getCityName($params['from']).' - '.getCityName($params['to']);

		// insert search into database
		$search = Search::create($params);
		if(!$search->id)
			displayMessage('Spremanje potražnje neuspješno.', 503);

		// check for offers
		$offers = $this->getOfferMatches($search);
		if (!$offers) {
            $response['offers'] = '';
            exit(json_encode($response));
        }

		// notification to offers
		$title = 'Potražnja prevoza';
		$user = User::fullName($search->user_id);
		$message = $user.' je objavio da traži prevoz, koji se podudara sa vašom ponudom.';
		$fb_response = sendNotifications($title, $message, $offers['regs'], $search, 'search');
		$response['firebase'] = $fb_response;

		// response to searcher
		$response['regs'] = $offers['regs'];
		$response['offers'] = $offers['offers'];

		echo json_encode($response, JSON_UNESCAPED_UNICODE);

		/*
		if success
			if no matches => message
			regs
			offers (array of obj)
			firebase
		else
			message...
		*/
	}

	public function searchRideCancel($request, $response){
	// 	required: id
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		// get all objects to work with
		$search = Search::find($params['id']);
		if (!$search)
			displayMessage('Pogrešan id.', 403);
		
		$user = User::fullName($search->user_id);
		$ride_requests = RideRequest::where('search_id', $search->id)->where('user_id', $search->user_id)->get();

		$offer_regs = array();

		// check / delete related requests
		foreach ($ride_requests as $rideRequest) {
			$delete_request = $rideRequest->deleteRequest();

			if (isset($delete_request['regs']))
				$offer_regs = array_merge($offer_regs, $delete_request['regs']);
		}

		$search->deleteRecord();

		if (count($offer_regs) > 0) {
			// notifications to searchers
			$title = 'Brisanje potražnje prevoza';
			$message = $user.' je obrisao zahtjev za prevozom...';
			$fb_response = sendNotifications($title, $message, $offer_regs, $search, 'search');
			$response['firebase'] = $fb_response;
		}	

		echo json_encode($response, JSON_UNESCAPED_UNICODE);

		/*
		if success
			if reqs => firebase
		else
			message...
		*/
	}

	public function searchRideUpdate($request, $response){
	//  required: id
	//  optional: user_id, from, to, seats, date, one_day, luggage
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		// get all objects / array of objects to work with
		$search = Search::find($params['id']);
		if (!$search)
			displayMessage('Pogrešan id.', 403);

		if (isset($params['seats']))
			$params['seats_start'] = $params['seats'];
		else
			$params['seats'] = $search->seats_start;

		if (isset($params['from']) || isset($params['to'])) {
			$description = explode(' - ', $search->description);
			$from = isset($params['from']) ? getCityName($params['from']) : $description[0];
			$to = isset($params['to']) ? getCityName($params['to']) : $description[1];
			$params['description'] = $from.' - '.$to;
		}

		$user = User::fullName($search->user_id);
		$ride_requests = RideRequest::where('search_id', $search->id)->where('user_id', $search->user_id)->get();

		$offer_regs_for_deleted_requests = array();

		// check / delete related requests
		foreach ($ride_requests as $rideRequest) {
			$delete_request = $rideRequest->deleteRequest();

			if (isset($delete_request['regs']))
				$offer_regs_for_deleted_requests = array_merge($offer_regs_for_deleted_requests, $delete_request['regs']);
		}

		// update record
		$beforeUpdate = clone $search;
		$search->updateRecord($params);

		// notification about deleted requests
		if (!empty($offer_regs_for_deleted_requests)) {
			$title = 'Izmjena potražnje prevoza';
			$message = $user.' je izmijenio potražnju prevoza "'.$beforeUpdate->description.'" od '.date('d.M.Y.', strtotime($beforeUpdate->date)).'. Vaš zahtjev za prevozom je obrisan!';
			$fb_response = sendNotifications($title, $message, $offer_regs_for_deleted_requests, $search, 'search');
			$response['firebase']['delete'] = $fb_response;
		}

		// check matched offers
		$offers = $this->getOfferMatches($search);
		if (!$offers) {
            $response['offers'] = '';
            exit(json_encode($response));
        }

		// notification to offerers
		$title = 'Izmjena potražnje prevoza';
		$message = $user.' je objavio da traži prevoz, koji se podudara sa vašom ponudom.';
		$fb_response = sendNotifications($title, $message, $offers['regs'], $search, 'search');
		$response['firebase']['update'] = $fb_response;

		// response to searcher
		$response['offers'] = $offers['offers'];
		echo json_encode($response, JSON_UNESCAPED_UNICODE);

		/*
		if success
			firebase['update']
			if offer matches 	=> offers
			if deleted requests => firebase['delete']
		else
			message...
		*/
	}

	public function getOfferMatches($search){
		$response = ['success' => 1];
		$offers = Offer::getMatches($search);
		$regs = array();

		foreach ($offers as $offer) {
			$offer->user = User::fullName($offer->user_id);
			$offer->date = date('d.M.Y.', strtotime($offer->date));
			$offer->time = substr($offer->time, 0, 5).'h';
			unset($offer->route);

			$response['offers'][] = $offer;
			$regs = array_merge($regs, Reg::where('user_id', $offer->user_id)->pluck('reg_id')->all());
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