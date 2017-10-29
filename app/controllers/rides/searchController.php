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

	// 29.10.
	public function rideSearch($request, $response){
	//	required: user_id, from, to, seats, date, one_day, luggage
		$params = $request->getParams();
		$required = ['user_id', 'from', 'to', 'seats', 'one_day', 'luggage'];
		checkRequiredFields($required, $params);
		
		$params['seats_start'] = $params['seats'];
		$response = array();

		$search = (object)[];
		$search->id = 99;
		foreach($params as $key => $value){
			$search->$key = $value;
		}

		// insert search into database
		/*$search = Search::create($params);
		if(!$search->id)
			displayMessage('Spremanje potražnje neuspješno.');*/

		// check for offers
		$offers = $this->checkOffers($search);
		if (!$offers)
			displayMessage('Nema podudaranja u ponudi', 1);

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
		RESPONSE
		{"firebase":{"payload":{"user":"Petar Petrović","route":{"id":99,"seats":"1","date":"26.Oct.2017.","one_day":"1","luggage":"1", "from":"Obudovac","to":"Derventa"}},
		"response":"{\"multicast_id\":7295176075466535364,\"success\":0,\"failure\":1,\"canonical_ids\":0,\"results\":[{\"error\":\"InvalidRegistration\"}]}"},
		"regs":["1234567890"],
		"offers":[{"id":1,"user_id":1,"date":"27.Oct.2017.","time":"13:00h","seats":2,"luggage":1,"user":"Marko Marković","from":"Brčko","to":"Banja Luka"}]}
		*/
	}

	public function cancelSearch($request, $response){
	// 	required: id
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		// get all objects to work with
		$search = Search::find($params['id']);
		if (!$offer)
			displayMessage('Pogrešan id...');
		
		$ride_requests = RideRequest::getAllByUserId($search->user_id);

		$response = array();
		$offer_regs = array();

		foreach ($ride_requests as $rideRequest) {

			if ($rideRequest->answer == 'accepted') {
				// uppdate offer
				$search = Search::find($rideRequest->search_id);
				if (!$search)
					displayMessage('Potražnja ne postoji u bazi.');

				$offer = Offer::find($rideRequest->offer_id);
				if (!$offer)
					displayMessage('Ponuda ne postoji u bazi.');

				$seats = $offer->seats + $search->seats_start;
				if (!$offer->updateRecord(['seats' => $seats]));
					displayMessage('Izmjena ponude neuspješna.');

				$offer_regs = array_merge($offer_regs, Reg::where('user_id', $offer->user_id)->pluck('reg_id')->all());
			}

			$rideRequest->deleteRecord();
		}

		$user = User::fullName($search->user_id);

		$search->deleteRecord();

		if (count($offer_regs) > 0) {
			// notifications to searchers
			$title = 'Brisanje potražnje prevoza';
			$message = $user.' je obrisao prevoz. Potražite novi!';
			$fb_response = sendNotifications($title, $message, $search_regs, $offer, 'offer');
			$response['firebase'] = $fb_response;
		}	

		$response['success'] = 1;
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}

	public function updateSearch($request, $response){
		//
	}

	public function checkOffers($search){

		$response = array();

		$offers = Offer::getMatches($search);
		if (!$offers)
			displayMessage('Nema podudaranja u ponudi', 1);

		$regs = array();

		foreach ($offers as $offer) {
			$offer_route_arr = explode(" - ", $offer->route);
			unset($offer->route);

			$offer->user = User::fullName($offer->user_id);
			$offer->from = getCityName($offer_route_arr[0]);
			$offer->to = getCityName(end($offer_route_arr));
			$offer->date = date('d.M.Y.', strtotime($offer->date));
			$offer->time = substr($offer->time, 0, 5).'h';

			$response['offers'][] = $offer;

			$regs = array_merge($regs, Reg::where('user_id', $offer->id)->pluck('reg_id')->all());
		}

		if (empty($regs))
			return false;
 		else {

 			$response['regs'] = $regs;
			return $response;
 		}
	}
	
}