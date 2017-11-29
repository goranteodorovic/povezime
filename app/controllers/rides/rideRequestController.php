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
            $response['ride_requests'] = '';
            exit(json_encode($response));
        }

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
		/*
		if success
			if no matches => ''
			ride_requests (array of objects)
		else
			message: ...
		*/
	}

	public function cancelRequest($request, $response){
	//	required: id
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		$response = ['success' => 1];

		$rideRequest = RideRequest::find($params['id']);
		if (!$rideRequest)
			displayMessage('Pogrešan id.', 403);

		$delete_request = $rideRequest->deleteRequest();

		if ($delete_request['success'] == 0)
			displayMessage('Brisanje zahtjeva neuspješno!', 503);

		if ($delete_request['regs']) {
			$title = 'Otkazivanje zahtjeva';
			$message = $delete_request['user'].' je otkazao zahtjev za prevoz.';
			$fb_response = sendNotifications($title, $message, $delete_request['regs'], $delete_request['object'], $delete_request['type']);
			$response['firebase'] = $fb_response;
		}
		
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		/*
		if success
			firebase (if regs)	
		else
			message...
		*/
	}

	// ride offer request / offering a ride
	public function offerRideRequest($request, $response){
	// 	required: user_id, search_id, offer_id
		$params = $request->getParams();
		$required = ['user_id', 'search_id', 'offer_id'];
		checkRequiredFields($required, $params);

		// find objects to work with
		$user = User::find($params['user_id']);
		$search = Search::find($params['search_id']);
		$offer = Offer::find($params['offer_id']);
		if (!$user || !$search || !$offer)
			displayMessage('Pogrešan id. Provjeriti korisnika, ponudu i potražnju!', 400);

		// check if searches still needs a ride
		if ($search->seats == 0)
			displayMessage('Provjeriti mjesta ponude i potražnje!', 400);

		// save request
		$params['type'] = 'offer';
		$params['answer'] = 'pending';

		$rideRequest = RideRequest::create($params);
		if (!$rideRequest)
			displayMessage('Čuvanje zahtjeva neuspješno!', 503);

		// send notification to searcher
		$title = 'Zahtjev/ponuda prevoza';
		$message = User::fullName($user->id).' vam je ponudio prevoz.';
		$search_regs = Reg::where('user_id', $search->user_id)->pluck('reg_id')->all();
		$fb_response = sendNotifications($title, $message, $search_regs, $offer, 'offer');
		$response['firebase'] = $fb_response;

		$response['request'] = $rideRequest;
		echo json_encode($response, JSON_UNESCAPED_UNICODE);

		/*
		if success
			request
			firebase
		else
			message:	...
		*/
	}

	// answer to ride offer
	public function offerRideAnswer($request, $response){
	// 	required: id, user_id, answer
		$params = $request->getParams();
		$required = ['id', 'user_id', 'answer'];
		checkRequiredFields($required, $params);

		// find objects to work with
		$rideRequest = RideRequest::find($params['id']);
		if (!$rideRequest)
			displayMessage('Pogrešan id.', 403);

		$user = User::find($params['user_id']);
		$search = Search::find($rideRequest->search_id);
		$offer = Offer::find($rideRequest->offer_id);
		if (!$user || !$search || !$offer)
			displayMessage('Pogrešan id. Provjeriti korisnika, ponudu i potražnju!', 400);

		if ($params['answer'] == 0) {
			$request_answer = 'denied';
			$fb_msg = 'odbio(la)';
		} else {

			if ($search->seats == 0 || $offer->seats < $search->seats) { 
				$response_msg = 'Zahtjev više nije dostupan!';
				$rideRequest->answer = 'denied';
			} else {
				$offer->updateRecord(['seats' => $offer->seats - $search->seats]);
				$search->updateRecord(['seats' => 0]);

				$request_answer = 'accepted';
				$fb_msg = 'prihvatio(la)';
			}
		}

		// update riderequest
		$rideRequest->updateRecord(['answer' => $request_answer]);

		if (isset($fb_msg)) {
			$title = 'Odgovor na ponuda prevoza';
			$message = User::fullName($search->user_id).' je '.$fb_msg.' vaš zahtjev za ponudu prevoza.';
			$offer_regs = Reg::where('user_id', $offer->user_id)->pluck('reg_id')->all();
			$fb_response = sendNotifications($title, $message, $offer_regs, $search, 'search');
			$response['firebase'] = $fb_response;
		}

		$response['message'] = isset($response_msg) ? $response_msg : 'Zahtjev je prošao.';
		echo json_encode($response, JSON_UNESCAPED_UNICODE);

		/*
		if success
			message...
			firebase
		else
			message...
		*/
	}

	// search ride request / requesting  a ride
	public function searchRideRequest($request, $response){
	// 	required: user_id, search_id, offer_id
		$params = $request->getParams();
		$required = ['user_id', 'search_id', 'offer_id'];
		checkRequiredFields($required, $params);

		// find objects to work with
		$user = User::find($params['user_id']);
		$search = Search::find($params['search_id']);
		$offer = Offer::find($params['offer_id']);
		if (!$user || !$search || !$offer)
			displayMessage('Pogrešan id. Provjeriti korisnika, ponudu i potražnju!', 400);

		// check if searches still needs a ride
		if ($search->seats == 0 || $offer->seats < $search->seats)
			displayMessage('Provjeriti mjesta ponude i potražnje!', 400);

		// save request
		$params['type'] = 'search';
		$params['answer'] = 'pending';

		$rideRequest = RideRequest::create($params);
		if (!$rideRequest)
			displayMessage('Čuvanje zahtjeva neuspješno!', 503);

		// send notification to searcher
		$title = 'Zahtjev/potražnja prevoza';
		$message = User::fullName($user->id).' potražuje prevoz.';
		$offer_regs = Reg::where('user_id', $offer->user_id)->pluck('reg_id')->all();
		$fb_response = sendNotifications($title, $message, $offer_regs, $search, 'search');
		$response['firebase'] = $fb_response;

		$response['request'] = $rideRequest;
		echo json_encode($response, JSON_UNESCAPED_UNICODE);

		/*
		if success
			request
			firebase
		else
			message:	...
		*/
	}

	// answer to ride search
	public function searchRideAnswer($request, $response){
		// 	required: id, user_id, answer
		$params = $request->getParams();
		$required = ['id', 'user_id', 'answer'];
		checkRequiredFields($required, $params);

		// find objects to work with
		$rideRequest = RideRequest::find($params['id']);
		if (!$rideRequest)
			displayMessage('Pogrešan id!', 403);

		$user = User::find($params['user_id']);
		$search = Search::find($rideRequest->search_id);
		$offer = Offer::find($rideRequest->offer_id);
		if (!$user || !$search || !$offer)
			displayMessage('Pogrešan id. Provjeriti korisnika, ponudu i potražnju!', 400);

		if ($params['answer'] == 0) {
			$request_answer = 'denied';
			$fb_msg = 'odbio(la)';
		} else {

			if ($search->seats == 0 || $offer->seats < $search->seats) { 
				$response_msg = 'Zahtjev više nije dostupan!';
				$rideRequest->answer = 'denied';
			} else {
				$offer->updateRecord(['seats' => $offer->seats - $search->seats]);
				$search->updateRecord(['seats' => 0]);

				$request_answer = 'accepted';
				$fb_msg = 'prihvatio(la)';
			}
		}

		// update ride request
		$rideRequest->updateRecord(['answer' => $request_answer]);

		if (isset($fb_msg)) {
			$title = 'Odgovor na potražnju prevoza';
			$message = User::fullName($offer->user_id).' je '.$fb_msg.' vaš zahtjev za potražnju prevoza.';
			$search_regs = Reg::where('user_id', $search->user_id)->pluck('reg_id')->all();
			$fb_response = sendNotifications($title, $message, $search_regs, $offer, 'offer');
			$response['firebase'] = $fb_response;
		}

		$response['message'] = isset($response_msg) ? $response_msg : 'Zahtjev je prošao.';
		echo json_encode($response, JSON_UNESCAPED_UNICODE);

		/*
		if success
			message...
			firebase
		else
			message...
		*/
	}

}