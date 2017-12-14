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

		// insert search into database
		$search = Search::create($params);
		if(!$search->id)
			displayMessage('Spremanje potražnje neuspješno.', 503);

		// check for offers
		$offers = $this->getOfferMatches($search);
		if (!$offers)
            exit(json_encode(array()));

        $search->user = User::findSpecific($params['user_id']);
        unset($search->user_id);
        $resp = ['search' => $search, 'offers' => $offers['offers']];
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);

		// notification to offers
		$title = 'Potražnja prevoza';
		$user_name = User::fullName($search->user->id);
		$message = $user_name.' je objavio da traži prevoz, koji se podudara sa vašom ponudom.';
		$firebase = sendNotifications($title, $message, $offers['regs'], $search, 'search');
        //echo ' -FIREBASE- ';
		//echo json_encode($firebase, JSON_UNESCAPED_UNICODE);
		/*
		if success
            search (obj), offers []
		else
            message...
		*/
	}

	public function searchRideCancel($request, $response){
	// 	required: id, user_id
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		// get all objects to work with
		$search = Search::find($params['id']);
		if (!$search)
            displayMessage('Pogrešan id.', 403);

		$ride_requests = RideRequest::where('search_id', $search->id)->where('user_id', $search->user_id)->get();

		$offer_regs = array();

		// check / delete related requests
		foreach ($ride_requests as $rideRequest) {
			$delete_request_regs = $rideRequest->deleteRequest();

            if (isset($delete_request_regs) && !empty($delete_request_regs))
                $offer_regs = array_merge($offer_regs, $delete_request_regs);
		}

		$search->deleteRecord();
        echo json_encode(['id'=>$params['id']]);

		if (count($offer_regs) > 0) {
			// notifications to searchers
			$title = 'Brisanje potražnje prevoza';
            $user_name = User::fullName($search->user_id);
			$message = $user_name.' je obrisao zahtjev za prevozom...';
            sendNotifications($title, $message, $offer_regs, $search, 'search');
		}

		/*
		if success
		    id
		else
			message...
		*/
	}

	public function searchRideUpdate($request, $response){
	//  required: id, user_id
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

		$ride_requests = RideRequest::where('search_id', $search->id)->where('user_id', $search->user_id)->get();

		$offer_regs_for_deleted_requests = array();

		// check / delete related requests
		foreach ($ride_requests as $rideRequest) {
            $delete_request_regs = $rideRequest->deleteRequest();

            if (isset($delete_request_regs) && !empty($delete_request_regs))
                $offer_regs_for_deleted_requests = array_merge($offer_regs_for_deleted_requests, $delete_request_regs);
		}

		// update record
		$beforeUpdate = clone $search;
		$search->updateRecord($params);

        $user_name = User::fullName($search->user_id);

		// notification about deleted requests
		if (!empty($offer_regs_for_deleted_requests)) {
			$title = 'Izmjena potražnje prevoza';
			$message = $user_name.' je izmijenio potražnju prevoza za: '.date('d.M.Y.', strtotime($beforeUpdate->date)).'. Vaš zahtjev za prevozom je obrisan!';
            $firebase = sendNotifications($title, $message, $offer_regs_for_deleted_requests, $search, 'search');
            //echo ' -FIREBASE- DELETED REQUESTS ';
            //echo json_encode($firebase, JSON_UNESCAPED_UNICODE);
		}

		// check matched offers
		$offers = $this->getOfferMatches($search);
		if (!$offers)
            exit(json_encode(array()));

        $search->user = User::findSpecific($search->user_id);
        unset($search->user_id);
        $resp = ['search' => $search, 'offers' => $offers['offers']];
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);

		// notification to offerers
		$title = 'Izmjena potražnje prevoza';
		$message = $user_name.' je objavio da traži prevoz, koji se podudara sa vašom ponudom.';
		$firebase = sendNotifications($title, $message, $offers['regs'], $search, 'search');
        //echo ' -FIREBASE- ';
        //echo json_encode($firebase, JSON_UNESCAPED_UNICODE);

		/*
		if success
            search (obj), offers []
		else
            message...
		*/
	}

	public function getOfferMatches($search){
		$offers = Offer::getMatches($search);
		$regs = array();

		foreach ($offers as $offer) {
			$offer->user = User::findSpecific($offer->user_id);
			unset($offer->user_id);

			/*$route_array = explode(' - ', $offer->route);
			$offer->from = $route_array[0];
			$offer->to = end($route_array);*/
			unset($offer->route);

            //$offer->date = date('d.M.Y.', strtotime($offer->date));
            //$offer->time = substr($offer->time, 0, 5).'h';

            $resp['offers'][] = $offer;
			$regs = array_merge($regs, Reg::where('user_id', $offer->user->id)->pluck('reg_id')->all());
		}

		if (empty($regs)) {
			return false;
		} else {
            $resp['regs'] = $regs;
			return $resp;
 		}

        /*
        if success
            offers []
            regs []
        else
            false
        */
	}
}