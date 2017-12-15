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

		$ride_requests = RideRequest::where('user_id', $params['user_id'])->get();

        foreach ($ride_requests as $rideRequest) {
            $rideRequest->type = ($rideRequest->type == 'S') ? 'search' : 'offer';

            if ($rideRequest->answer == 'A') { $rideRequest->answer = 'accepted'; }
            else if ($rideRequest->answer == 'D') { $rideRequest->answer = 'denied'; }
            else { $rideRequest->answer = 'pending'; }

            $search = Search::findSpecific($rideRequest->search_id);
            $offer = Offer::findSpecific($rideRequest->offer_id);

            $rideRequest->search = $search;
            $rideRequest->offer = $offer;
            unset($rideRequest->search_id, $rideRequest->offer_id);

            unset($rideRequest->user_id, $rideRequest->created_at, $rideRequest->updated_at);

        }

		echo json_encode($ride_requests, JSON_UNESCAPED_UNICODE);

		/*
		if success
			ride_requests [ rideRequest(id, type, answer, search Obj, offer Obj)]
		else
			message...
		*/
	}

    public function cancelRequest($request, $response){
	//	required: id, user_id
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		$rideRequest = RideRequest::find($params['id']);
		if (!$rideRequest)
			displayMessage('Pogrešan id.', 403);

        $type = $rideRequest->type;
        if ($rideRequest->type == 'S')
            $obj = Search::find($rideRequest->search_id);
        else
            $obj = Offer::find($rideRequest->offer_id);

		$delete_request_regs = $rideRequest->deleteRequest();
		echo $params['id'];
		//echo json_encode(['id'=>$params['id']]);

		if (!empty($delete_request_regs)) {
			$title = 'Otkazivanje zahtjeva';
            $user_name = User::fullName($rideRequest->user_id);
            $message = $user_name.' je otkazao zahtjev za prevoz.';
			$firebase = sendNotifications($title, $message, $delete_request_regs, $obj, $type);
            //echo ' -FIREBASE- ';
            //echo json_encode($firebase, JSON_UNESCAPED_UNICODE);
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
		$search = Search::findSpecific($params['search_id']);
		$offer = Offer::findSpecific($params['offer_id']);
		if (!$search || !$offer)
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

		$rideRequest->type = 'offer';
        $rideRequest->answer = 'pending';
        $rideRequest->search = $search;
		$rideRequest->offer = $offer;
        unset($rideRequest->search_id, $rideRequest->offer_id);

        echo json_encode($rideRequest, JSON_UNESCAPED_UNICODE);

		// send notification to searcher
		$title = 'Zahtjev/ponuda prevoza';
        $user_name = User::fullName($params['user_id']);
		$message = $user_name.' vam je ponudio prevoz.';
		$search_regs = Reg::where('user_id', $search->user->id)->pluck('reg_id')->all();
		$firebase = sendNotifications($title, $message, $search_regs, $offer, 'offer');
        //echo ' -FIREBASE- ';
        //echo json_encode($firebase, JSON_UNESCAPED_UNICODE);

		/*
		if success
			request (obj)
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

		$search = Search::find($rideRequest->search_id);
		$offer = Offer::find($rideRequest->offer_id);
		if (!$search || !$offer)
			displayMessage('Pogrešan id. Provjeriti korisnika, ponudu i potražnju!', 400);

		if ($params['answer'] == 'D') {
			$fb_msg = 'odbio(la)';
		} else {

			if ($search->seats == 0 || $offer->seats < $search->seats) { 
				//$response_msg = 'Zahtjev više nije dostupan!';
				$params['answer'] = 'D';
			} else {
				$offer->updateRecord(['seats' => $offer->seats - $search->seats]);
				$search->updateRecord(['seats' => 0]);

				$fb_msg = 'prihvatio(la)';
			}
		}

		// update ride request
        $rideRequest->updateRecord(['answer' => $params['answer']]);
		echo $params['id'];
        //echo json_encode(['id'=>$params['id']]);

		if (isset($fb_msg)) {
			$title = 'Odgovor na ponuda prevoza';
            $user_name = User::fullName($params['user_id']);
			$message = $user_name.' je '.$fb_msg.' vaš zahtjev za ponudu prevoza.';
			$offer_regs = Reg::where('user_id', $offer->user_id)->pluck('reg_id')->all();
			$firebase = sendNotifications($title, $message, $offer_regs, $search, 'search');
            //echo ' -FIREBASE- ';
            //echo json_encode($firebase, JSON_UNESCAPED_UNICODE);
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
		//$user = User::find($params['user_id']);
		$search = Search::findSpecific($params['search_id']);
		$offer = Offer::findSpecific($params['offer_id']);
		if (!$search || !$offer)
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

        $rideRequest->type = 'search';
        $rideRequest->answer = 'pending';
        $rideRequest->search = $search;
        $rideRequest->offer = $offer;
        unset($rideRequest->search_id, $rideRequest->offer_id);

        echo json_encode($rideRequest, JSON_UNESCAPED_UNICODE);

		// send notification to searcher
		$title = 'Zahtjev/potražnja prevoza';
        $user_name = User::fullName($params['user_id']);
		$message = $user_name.' potražuje prevoz.';
		$offer_regs = Reg::where('user_id', $offer->user_id)->pluck('reg_id')->all();
		$firebase = sendNotifications($title, $message, $offer_regs, $search, 'search');
        //echo ' -FIREBASE- ';
        //echo json_encode($firebase, JSON_UNESCAPED_UNICODE);

		/*
		if success
			request (obj)
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


		$search = Search::find($rideRequest->search_id);
		$offer = Offer::find($rideRequest->offer_id);
		if (!$search || !$offer)
			displayMessage('Pogrešan id. Provjeriti korisnika, ponudu i potražnju!', 400);

		if ($params['answer'] == 'D') {
			$fb_msg = 'odbio(la)';
		} else {

			if ($search->seats == 0 || $offer->seats < $search->seats) { 
				//$response_msg = 'Zahtjev više nije dostupan!';
                $params['answer'] = 'D';
			} else {
				$offer->updateRecord(['seats' => $offer->seats - $search->seats]);
				$search->updateRecord(['seats' => 0]);

				$fb_msg = 'prihvatio(la)';
			}
		}

		// update ride request
		$rideRequest->updateRecord(['answer' => $params['answer']]);
        echo $params['id'];
        //echo json_encode(['id'=>$params['id']]);

		if (isset($fb_msg)) {
			$title = 'Odgovor na potražnju prevoza';
            $user_name = User::fullName($params['user_id']);
			$message = $user_name.' je '.$fb_msg.' vaš zahtjev za potražnju prevoza.';
			$search_regs = Reg::where('user_id', $search->user_id)->pluck('reg_id')->all();
			$firebase = sendNotifications($title, $message, $search_regs, $offer, 'offer');
            //echo ' -FIREBASE- ';
            //echo json_encode($firebase, JSON_UNESCAPED_UNICODE);
		}

		/*
		if success
			id
		else
			message...
		*/
	}

}