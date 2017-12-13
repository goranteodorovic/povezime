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
		
		$params['seats_start'] = $params['seats'];

		// insert offer into database
		$offer = Offer::create($params);
		if(!$offer->id)
			displayMessage('Spremanje ponude neuspješno.', 503);

		// check for searches
		$searches = $this->getSearchMatches($offer);
        if (!$searches)
            exit(json_encode(array()));

        $offer->user = User::findSpecific($params['user_id']);
        unset($offer->user_id);
        $resp = ['offer' => $offer, 'searches' => $searches['searches']];
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);

		// notification to searches
		$title = 'Ponuda prevoza';
		$user = User::fullName($offer->user->id);
		$message = $user.' je objavio da nudi prevoz, koji se podudara sa vašom potražnjom.';
		$firebase = sendNotifications($title, $message, $searches['regs'], $offer, 'offer');
		//echo ' -FIREBASE- ';
        //echo json_encode($firebase, JSON_UNESCAPED_UNICODE);

		/*
		if success
			offer (obj), searches []
		else
			message...
		*/
	}

	public function offerRideCancel($request, $response){
	// 	required: id, user_id
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
            $delete_request_regs = $rideRequest->deleteRequest();

			if (isset($delete_request_regs) && !empty($delete_request_regs))
                $search_regs = array_merge($search_regs, $delete_request_regs);
		}

		$offer->deleteRecord();
        echo json_encode(['id'=>$params['id']]);

		if (count($search_regs) > 0) {
			// notifications to searchers
			$title = 'Brisanje ponude prevoza';
			$message = $user.' je obrisao prevoz. Potražite novi!';
			sendNotifications($title, $message, $search_regs, $offer, 'offer');
		}

		/*
		if success
		    id
		else
			message...
		*/
	}

	public function offerRideUpdate($request, $response){
	//  required: id, user_id
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

		$user = User::fullName($offer->user_id);
		$ride_requests = RideRequest::where('offer_id', $offer->id)->where('user_id', $offer->user_id)->get();

		$search_regs_for_deleted_requests = array();
		
		// check / delete related requests
		foreach ($ride_requests as $rideRequest) {
			$delete_request_regs = $rideRequest->deleteRequest();

			if (isset($delete_request_regs) && !empty($delete_request_regs))
                $search_regs_for_deleted_requests = array_merge($search_regs_for_deleted_requests, $delete_request_regs);
		}

		// update record
		$beforeUpdate = clone $offer;
		$offer->updateRecord($params);

		// notification about deleted requests
		if (!empty($search_regs_for_deleted_requests)) {
			$title = 'Izmjena ponude prevoza';
			$message = $user.' je izmijenio ponudu prevoza za '.date('d.M.Y.', strtotime($beforeUpdate->date)).'. Vaš zahtjev za prevozom je obrisan!';
            $firebase = sendNotifications($title, $message, $search_regs_for_deleted_requests, $offer, 'offer');
            //echo ' -FIREBASE- ';
            //echo json_encode($firebase, JSON_UNESCAPED_UNICODE);
		}

		// check matched searches
		$searches = $this->getSearchMatches($offer);
		if (!$searches)
            exit(json_encode(array()));

        $offer->user = User::findSpecific($offer->user_id);
        unset($offer->user_id);
        $resp = ['offer' => $offer, 'searches' => $searches['searches']];
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);

		// notification to searches
		$title = 'Izmjena ponude prevoza';
		$message = $user.' je objavio da nudi prevoz, koji se podudara sa vašom potražnjom.';
		$firebase = sendNotifications($title, $message, $searches['regs'], $offer, 'offer');
        //echo ' -FIREBASE- ';
        //echo json_encode($firebase, JSON_UNESCAPED_UNICODE);

		/*
		if success
			offer (obj), searches []
		else
			message...
		*/
	}


	public function getSearchMatches($offer){
		$searches = Search::getMatches($offer);
		$regs = array();

		foreach ($searches as $search) {
		    $search->user = User::findSpecific($search->user_id);
		    unset($search->user_id);

			$search->date = date('d.M.Y.', strtotime($search->date));
			unset($search->from, $search->to);

            $resp['searches'][] = $search;
			$regs = array_merge($regs, Reg::where('user_id', $search->user->id)->pluck('reg_id')->all());
		}

		if (empty($regs)) {
			return false;
		} else {
            $resp['regs'] = $regs;
			return $resp;
 		}

 		/*
 		if success
 		    searches []
 		    regs []
 		else
 		    false
 		*/
	}
}