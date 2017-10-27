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
	//	required: user_id
		$params = $request->getParams();
		$required = ['user_id'];
		checkRequiredFields($required, $params);

		$ride_requests = RideRequest::getAllByUserId($params['user_id']);
		if (!isset($ride_requests) || count($ride_requests) == 0) {
			displayMessage('Nema rezultata.');
		}

		$response = array();

		foreach ($ride_requests as $key => $rideRequest) {

			$search = Search::find($rideRequest->search_id);
			$offer = Offer::find($rideRequest->offer_id);

			if($rideRequest->type == 'search')
				$user = User::find($offer->user_id);
			else
				$user = User::find($search->user_id);

			$rideRequest->search = $search->description;
			$rideRequest->offer = $offer->description;
			$rideRequest->date = date('d.M.Y.', strtotime($offer->date));
			$rideRequest->user = User::fullName($user->id);

			unset($rideRequest->search_id, $rideRequest->offer_id, $rideRequest->user_id);
		}

		echo json_encode($ride_requests, JSON_UNESCAPED_UNICODE);
	}

	// cancel ride request
	public function cancelRequest($request, $response){
	//	required: id
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		// get all objects to work with
		$rideRequest = RideRequest::find($params['id']);
		if (!$rideRequest) {
			displayMessage('Pogrešan id...');
		}
		
		$type = $rideRequest->type;
		$answer = $rideRequest->answer;

		$search = Search::where('id', $rideRequest->search_id)->first();
		$offer = Offer::where('id', $rideRequest->offer_id)->first();
		if (!$search || !$offer) {
			displayMessage('Došlo je do greške... Ponuda/Potražnja ne postoji u bazi.');
		}

		if ($answer == 'accepted') {
			// update seats in search and offer tables
			if (!$search->updateRecord(['seats' => $search->seats_start])) {
				displayMessage('Izmjena ponude neuspješna.');
			}

			if (!$offer->updateRecord(['seats' => $offer->seats + $search->seats_start])) {
				displayMessage('Izmjena ponude neuspješna.');
			}
		}

		$rideRequest->deleteRecord();

		$response = array();

		if ($answer != 'denied') {
			// send notification
			$title = 'Otkazivanje zahtjeva';
			$message = User::fullName($rideRequest->user_id).' je otkazao zahtjev za prevoz.';
			if ($type == 'search') {
				$offer_regs = Reg::where('user_id', $offer->user_id)->pluck('reg_id')->all();
				$fb_response = sendNotifications($title, $message, $offer_regs, $search, 'search');
			} else {
				$search_regs = Reg::where('user_id', $search->user_id)->pluck('reg_id')->all();
				$fb_response = sendNotifications($title, $message, $search_regs, $offer, 'offer');
			}

			$response['firebase'] = $fb_response;
		}

		$response['success'] = 1;
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
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
			displayMessage('Zahtjev nije moguć. Provjerite mjesta ponude i potražnje!');
		}

		// save request
		$params['type'] = 'offer';
		$params['answer'] = 'pending';

		$rideRequest = RideRequest::create($params);
		if (!$rideRequest) {
			displayMessage('Čuvanje zahtjeva neuspješno!');
		}

		// send notification to searcher
		$title = 'Zahtjev/ponuda prevoza';
		$message = User::fullName($user->id).' vam je ponudio prevoz.';
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
		$rideRequest = RideRequest::find($params['id']);
		if (!$rideRequest) {
			displayMessage('Došlo je do greške... Pogrešan id!');
		}

		$user = User::find($params['user_id']);
		$search = Search::find($rideRequest->search_id);
		$offer = Offer::find($rideRequest->offer_id);
		if (!$user || !$search || !$offer) { 
			displayMessage('Došlo je do greške... Provjerite korisnika, ponudu i potražnju!'); 
		}

		if ($params['answer'] == 0) {

			$request_answer = 'denied';
			$fb_msg = 'odbio(la)';
		} else {

			if ($search->seats == 0 || $offer->seats < $search->seats) { 
				$response_msg = 'Zahtjev više nije dostupan!';
				$rideRequest->answer = 'denied';
			} else {
				// update offer
				$offer_seats = $offer->seats - $search->seats;
				if (!$offer->updateRecord(['seats' => $offer_seats])) {
					displayMessage('Izmjena ponude neuspješna.');
				}
				// update search
				if (!$search->updateRecord(['seats' => 0])) {
					displayMessage('Izmjena potražnje neuspješna.');
				}

				$request_answer = 'accepted';
				$fb_msg = 'prihvatio(la)';
			}
		}

		// update riderequest
		if (!$rideRequest->updateRecord(['answer' => $request_answer])) {
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
			displayMessage('Došlo je do greške... Provjerite korisnika, ponudu i potražnju!');
		}

		// check if searches still needs a ride
		if ($search->seats == 0 || $offer->seats < $search->seats) {
			displayMessage('Zahtjev nije moguć. Provjerite mjesta ponude i potražnje!');
		}

		// save request
		$params['type'] = 'search';
		$params['answer'] = 'pending';

		$rideRequest = RideRequest::create($params);
		if (!$rideRequest) {
			displayMessage('Čuvanje zahtjeva neuspješno!');
		}

		// send notification to searcher
		$title = 'Zahtjev/potražnja prevoza';
		$message = User::fullName($user->id).' potražuje prevoz.';
		
		$offer_regs = Reg::where('user_id', $offer->user_id)->pluck('reg_id')->all();
		$fb_response = sendNotifications($title, $message, $offer_regs, $search, 'search');
		$response['firebase'] = $fb_response;

		$response['request'] = $rideRequest;
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}

	public function searchRideAnswer($request, $response){
		// 	required: id, user_id, answer
		$params = $request->getParams();
		$required = ['id', 'user_id', 'answer'];
		checkRequiredFields($required, $params);

		$response = array();

		// find objects to work with
		$rideRequest = RideRequest::find($params['id']);
		if (!$rideRequest) {
			displayMessage('Došlo je do greške... Pogrešan id!');
		}

		$user = User::find($params['user_id']);
		$search = Search::find($rideRequest->search_id);
		$offer = Offer::find($rideRequest->offer_id);
		if (!$user || !$search || !$offer) { 
			displayMessage('Došlo je do greške... Provjerite korisnika, ponudu i potražnju!'); 
		}

		if ($params['answer'] == 0) {

			$request_answer = 'denied';
			$fb_msg = 'odbio(la)';
		} else {

			if ($search->seats == 0 || $offer->seats < $search->seats) { 
				$response_msg = 'Zahtjev više nije dostupan!';
				$rideRequest->answer = 'denied';
			} else {
				// update offer
				$offer_seats = $offer->seats - $search->seats;
				if (!$offer->updateRecord(['seats' => $offer_seats])) {
					displayMessage('Izmjena ponude neuspješna.');
				}
				// update search
				if (!$search->updateRecord(['seats' => 0])) {
					displayMessage('Izmjena potražnje neuspješna.');
				}

				$request_answer = 'accepted';
				$fb_msg = 'prihvatio(la)';
			}
		}

		// update riderequest
		if (!$rideRequest->updateRecord(['answer' => $request_answer])) {
			displayMessage('Izmjena zahtjeva neuspješna.');
		}

		if (isset($fb_msg)) {
			$title = 'Odgovor na potražnju prevoza';

			$message = User::fullName($offer->user_id).' je '.$fb_msg.' vaš zahtjev za potražnju prevoza.';

			$search_regs = Reg::where('user_id', $search->user_id)->pluck('reg_id')->all();
			$fb_response = sendNotifications($title, $message, $search_regs, $offer, 'offer');
			$response['firebase'] = $fb_response;
		}

		$response['message'] = isset($response_msg) ? $response_msg : 'Zahtjev je prošao.';
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}

}