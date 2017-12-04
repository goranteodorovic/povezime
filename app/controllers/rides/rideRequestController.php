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
		if (!isset($ride_requests) || count($ride_requests) == 0)
            exit(json_encode(array()));

		foreach ($ride_requests as $key => $rideRequest) {

			$search = Search::find($rideRequest->search_id);
			$offer = Offer::find($rideRequest->offer_id);

			if($rideRequest->type == 'S')
                $rideRequest->user_name = User::fullName($offer->user_id);
			else
                $rideRequest->user_name = User::fullName($search->user_id);

            $rideRequest->date = date('d.M.Y.', strtotime($offer->date));
			//unset($rideRequest->user_id);
		}

		echo json_encode($ride_requests, JSON_UNESCAPED_UNICODE);

		/*
		if success
			ride_requests
		else
			message...
		*/
	}

    public function cancelRequest($request, $response){
	//	required: id
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		$rideRequest = RideRequest::find($params['id']);
		if (!$rideRequest)
			displayMessage('Pogrešan id.', 403);

        $user = User::find($rideRequest->user_id);
        $type = $rideRequest->type;
        if ($rideRequest->type == 'S')
            $obj = Search::find($rideRequest->search_id);
        else
            $obj = Offer::find($rideRequest->offer_id);

		$delete_request_regs = $rideRequest->deleteRequest();
		echo json_encode(['id'=>$params['id']]);

		if (!empty($delete_request_regs)) {
			$title = 'Otkazivanje zahtjeva';
			$message = $user.' je otkazao zahtjev za prevoz.';
			sendNotifications($title, $message, $delete_request_regs, $obj, $type);
		}

		/*
		if success
			id
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
		$params['type'] = 'O';
		$params['answer'] = 'P';

		$rideRequest = RideRequest::create($params);
		if (!$rideRequest)
			displayMessage('Čuvanje zahtjeva neuspješno!', 503);

		// send notification to searcher
		$title = 'Zahtjev/ponuda prevoza';
		$message = User::fullName($user->id).' vam je ponudio prevoz.';
		$search_regs = Reg::where('user_id', $search->user_id)->pluck('reg_id')->all();
		sendNotifications($title, $message, $search_regs, $offer, 'offer');

        echo json_encode($rideRequest, JSON_UNESCAPED_UNICODE);

		/*
		if success
			request
		else
			message...
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

		if ($params['answer'] == 'D') {
			//$request_answer = 'D';
			$fb_msg = 'odbio(la)';
		} else {

			if ($search->seats == 0 || $offer->seats < $search->seats) { 
				$response_msg = 'Zahtjev više nije dostupan!';
				//$rideRequest->answer = 'D';
				$params['answer'] = 'D';
			} else {
				$offer->updateRecord(['seats' => $offer->seats - $search->seats]);
				$search->updateRecord(['seats' => 0]);

				//$request_answer = 'A';
				$fb_msg = 'prihvatio(la)';
			}
		}

		// update ride request
        $rideRequest->updateRecord(['answer' => $params['answer']]);
        echo json_encode(['id'=>$params['id']]);

		if (isset($fb_msg)) {
			$title = 'Odgovor na ponuda prevoza';
			$message = User::fullName($search->user_id).' je '.$fb_msg.' vaš zahtjev za ponudu prevoza.';
			$offer_regs = Reg::where('user_id', $offer->user_id)->pluck('reg_id')->all();
			sendNotifications($title, $message, $offer_regs, $search, 'search');
		}

		/*
		if success
			id
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
		$params['type'] = 'S';
		$params['answer'] = 'P';

		$rideRequest = RideRequest::create($params);
		if (!$rideRequest)
			displayMessage('Čuvanje zahtjeva neuspješno!', 503);

		// send notification to searcher
		$title = 'Zahtjev/potražnja prevoza';
		$message = User::fullName($user->id).' potražuje prevoz.';
		$offer_regs = Reg::where('user_id', $offer->user_id)->pluck('reg_id')->all();
		sendNotifications($title, $message, $offer_regs, $search, 'search');

        echo json_encode($rideRequest, JSON_UNESCAPED_UNICODE);

		/*
		if success
			request
		else
			message...
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

		if ($params['answer'] == 'D') {
			//$request_answer = 'D';
			$fb_msg = 'odbio(la)';
		} else {

			if ($search->seats == 0 || $offer->seats < $search->seats) { 
				$response_msg = 'Zahtjev više nije dostupan!';
				//$rideRequest->answer = 'D';
                $params['answer'] = 'D';
			} else {
				$offer->updateRecord(['seats' => $offer->seats - $search->seats]);
				$search->updateRecord(['seats' => 0]);

				//$request_answer = 'A';
				$fb_msg = 'prihvatio(la)';
			}
		}

		// update ride request
		$rideRequest->updateRecord(['answer' => $params['answer']]);

		if (isset($fb_msg)) {
			$title = 'Odgovor na potražnju prevoza';
			$message = User::fullName($offer->user_id).' je '.$fb_msg.' vaš zahtjev za potražnju prevoza.';
			$search_regs = Reg::where('user_id', $search->user_id)->pluck('reg_id')->all();
			sendNotifications($title, $message, $search_regs, $offer, 'offer');
		}

        echo json_encode(['id'=>$params['id']]);

		/*
		if success
			id
		else
			message...
		*/
	}

}