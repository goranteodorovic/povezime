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

	// 29.10
	public function rideOffer($request, $response){
	// 	required: user_id, route, car_id, seats, date, time, luggage
		$params = $request->getParams();
		$required = ['user_id', 'route', 'car_id', 'seats', 'date', 'time', 'luggage'];
		checkRequiredFields($required, $params);

		$params['seats_start'] = $params['seats'];
		$response = array();

		$offer = (object)[];
		$offer->id = 99;
		foreach($params as $key => $value){
			$offer->$key = $value;
		}

		// insert offer into database
		/*$offer = Offer::create($params);
		if(!$offer->id)
			displayMessage('Spremanje ponude neuspješno.');
		unset($offer->created_at, $offer->updated_at);*/

		// check for searches
		$searches = $this->checkSearches($offer);
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
		RESPONSE
		{"firebase":{"payload":{"user":"Marko Marković","car":"Ford Focus ST", "route":{"id":99,"seats":"2","date":"27.Oct.2017.","time":"13:00","luggage":"1","from":"Brčko","to":"Banja Luka"}},
		"response":"{\"multicast_id\":6273839410365308174,\"success\":0,\"failure\":1,\"canonical_ids\":0,\"results\":[{\"error\":\"InvalidRegistration\"}]}"},
		"regs":["1234567890"],
		"searches":[{"id":1,"user_id":2,"from":"Obudovac","to":"Derventa","date":"26.Oct.2017.","one_day":1,"seats":1,"luggage":1,"user":"Petar Petrović"}]}
		*/
	}

	// 28.10.
	public function cancelOffer($request, $response){
	// 	required: id
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		// get all objects to work with
		$offer = offer::find($params['id']);
		if (!$offer)
			displayMessage('Pogrešan id...');
		
		$ride_requests = RideRequest::getAllByUserId($offer->user_id);

		$response = array();
		$search_regs = array();

		foreach ($ride_requests as $rideRequest) {

			if ($rideRequest->answer == 'accepted') {
				// uppdate search
				$search = Search::find($rideRequest->search_id);
				if (!$search->updateRecord(['seats' => $search->seats_start]))
					displayMessage('Izmjena potražnje neuspješna.');

				$search_regs = array_merge($search_regs, Reg::where('user_id', $search->user_id)->pluck('reg_id')->all());
			}

			$rideRequest->deleteRecord();
		}

		$user = User::fullName($offer->user_id);

		$offer->deleteRecord();

		if (count($search_regs) > 0) {
			// notifications to searchers
			$title = 'Brisanje ponude prevoza';
			$message = $user.' je obrisao prevoz. Potražite novi!';
			$fb_response = sendNotifications($title, $message, $search_regs, $offer, 'offer');
			$response['firebase'] = $fb_response;
		}	

		$response['success'] = 1;
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}

	public function updateOffer($request, $response){
	//  required: id
	//  optional: user_id, route, car_id, seats, date, time, luggage
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		$response = array();

		// get all objects/array of objects to work with
		$offer = offer::find($params['id']);
		if (!$offer)
			displayMessage('Pogrešan id...');

		$user = User::fullName($offer->user_id);

		$search_regs_for_deleted_requests = array();

		$ride_requests = RideRequest::getAllByUserId($offer->user_id);
		
		if ($ride_requests) {
		// cancel requests
			foreach ($ride_requests as $rideRequest) {

				if ($rideRequest->answer == 'accepted') {
					// uppdate search
					$search = Search::find($rideRequest->search_id);
					if (!$search->updateRecord(['seats' => $search->seats_start]))
						displayMessage('Izmjena potražnje neuspješna.');

					$search_regs_for_deleted_requests = array_merge($search_regs_for_deleted_requests, Reg::where('user_id', $search->user_id)->pluck('reg_id')->all());
				}

				$rideRequest->deleteRecord();
			}
		}

		

		if (!empty($search_regs_for_deleted_requests)) {
			$title = 'Izmjena ponude prevoza';
			$message = $user.' je izmijenio ponudu prevoza. Provjerite da li vam isti odgovara ili potražite novi!';
			$fb_response = sendNotifications($title, $message, $search_regs_for_deleted_requests, $offer, 'offer');
			$response['firebase']['deleted_requests'] = $fb_response;
		}

		// update offer
		if (isset($params['seats']))
			$params['seats_start'] = $params['seats'];

		if (!$offer->updateRecord($params))
			displayMessage('Izmjena potražnje neuspješna.');

		// check matched searches
		$searches = $this->checkSearches($offer);
		if (!$searches)
			displayMessage('Nema podudaranja u potražnji', 1);

		// notification to searches
		$title = 'Izmjena ponude prevoza';
		$message = $user.' je objavio da nudi prevoz, koji se podudara sa vašom potražnjom.';
		$fb_response = sendNotifications($title, $message, $searches['search_regs'], $offer, 'offer');
		$response['firebase']['changed_offer'] = $fb_response;

		// response to offerer
		$response['searches'] = $searches;
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}


	public function checkSearches($offer){

		$response = array();

		$searches = Search::getMatches($offer);
		if (!$searches)
			displayMessage('Nema podudaranja u potražnji', 1);

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

		if (empty($regs))
			return false;
 		else {

 			$response['regs'] = $regs;
			return $response;
 		}
	}

}