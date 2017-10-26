<?php

namespace App\Controllers\Rides;

require_once dirname(__FILE__) . '/../../functions.php';

use App\Controllers\Controller;

use App\Models\User;
use App\Models\Reg;
use App\Models\Car;
use App\Models\Search;
use App\Models\Offer;
use App\Models\RideRequest;

Class RideRequestController extends Controller {

	public function getAllRequests($request, $response){

	}

	public function cancelRequest($request, $response){

	}

	// ride offer request
	public function offerRideRequest($request, $response){
	// 	required: user_id, search_id, offer_id
		$params = $request->getParams();
		$required = ['user_id', 'search_id', 'offer_id'];
		checkRequiredFields($required, $params);

		$response = array();

		// find objects to work with
		$user = User::find($params['user_id']);
		$search = Search::find($params['search_id']);
		$offer = Offer::find($params['offer_id']);
		if (!$user || !$search || !$offer) {
			displayMessage('Zahtjev nije moguć. Provjerite korisnika, ponudu i potražnju!');
		}

		// check if searches still needs a ride
		if ($search->seats == 0) {
			displayMessage('Zahtjev nije moguć. Potraživaču više nije potreban prevoz.');
		}

		// save request
		$params['type'] = 'offer';
		$params['answer'] = 'pending';

		/*$rideRequest = (object)[];
		$rideRequest->id = 99;
		foreach ($params as $key => $value) {
			$rideRequest->$key = $value;
		}*/

		$rideRequest = RideRequest::create($params);

		if (!$rideRequest) {
			displayMessage('Čuvanje zahtjeva neuspješno!');
		}

		// send notification to searcher
		$title = 'Zahtjev/ponuda prevoza';
		$message = User::fullName($offer->user_id).' vam je ponudio prevoz.';
		$search_regs = Reg::where('user_id', $search->user_id)->pluck('reg_id')->all();
		$fb_response = sendNotifications($title, $message, $search_regs, $offer, 'offer');
		$response['firebase'] = $fb_response;

		$response['request'] = $rideRequest;
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}

	// answer to ride offer
	public function offerRideAnswer($request, $response){
		// 	required: id, user_id, answer
		$params = $request->getParams();
		$required = ['id', 'user_id', 'answer'];
		checkRequiredFields($required, $params);

		$response = array();

		// find objects to work with
		$user = User::find($params['user_id']);
		$rideRequest = RideRequest::find($params['id']);
		$search = Search::find($rideRequest->search_id);
		$offer = Offer::find($rideRequest->offer_id);
		if (!$user || !$rideRequest || !$search || !$offer) { 
			displayMessage('Zahtjev nije moguć. Provjerite korisnika, ponudu i potražnju!'); 
		}

		if ($params['answer'] == 0) {

			$request_answer = 'denied';
			$fb_msg = 'Vaš zahtjev za ponudu prevoza je odbijen.';

		} else {

			if ($search->seats == 0 || $offer->seats < $search->seats) { 
				$response_msg = 'Zahtjev za prevozom je prekinut!';
				$rideRequest->answer = 'denied';
			} else {
				// update offer
				$offer_seats = $offer->seats - $search->seats;
				if (!$offer->updateRecordNew(['seats' => $offer_seats])) {
					displayMessage('Izmjena ponude neuspješna.');
				}
				// update search
				if (!$search->updateRecordNew(['seats' => 0])) {
					displayMessage('Izmjena potražnje neuspješna.');
				}

				$request_answer = 'accepted';
				$fb_msg = 'Vaš zahtjev za ponudu prevoza je prihvaćen.';
			}
		}

		// update riderequest
		if (!$rideRequest->updateRecordNew(['answer' => $request_answer])) {
			displayMessage('Izmjena zahtjeva neuspješna.');
		}

		if (isset($fb_msg)) {
			$title = 'Odgovor na ponuda prevoza';
			$message = User::fullName($search->user_id).' je '.$fb_msg.' vaš zahtjev za ponudu prevoza.';
			$offer_regs = Reg::where('user_id', $offer->user_id)->pluck('reg_id')->all();
			$fb_response = sendNotifications($title, $message, $offer_regs, $search, 'search');
			$response['firebase'] = $fb_response;
		}

		$response['message'] = isset($response_msg) ? $response_msg : 'Zahtjev je prošao.';
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}

	public function searchRideRequest($request, $response){
		//
	}

	public function searchRideAnswer($request, $response){
		//
	}

}