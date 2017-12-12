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

        $resp = ['search_id' => $search->id, 'offers' => $offers['offers']];
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);

		// notification to offers
		$title = 'Potražnja prevoza';
		$user = User::fullName($search->user_id);
		$message = $user.' je objavio da traži prevoz, koji se podudara sa vašom ponudom.';
		sendNotifications($title, $message, $offers['regs'], $search, 'search');

		/*
		if success
            search_id, offers []
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
			$delete_request_regs = $rideRequest->deleteRequest();

            if (isset($delete_request_regs) && !empty($delete_request_regs))
                $offer_regs = array_merge($offer_regs, $delete_request_regs);
		}

		$search->deleteRecord();
        echo json_encode(['id'=>$params['id']]);

		if (count($offer_regs) > 0) {
			// notifications to searchers
			$title = 'Brisanje potražnje prevoza';
			$message = $user.' je obrisao zahtjev za prevozom...';
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

		$user = User::fullName($search->user_id);
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

		// notification about deleted requests
		if (!empty($offer_regs_for_deleted_requests)) {
			$title = 'Izmjena potražnje prevoza';
			$message = $user.' je izmijenio potražnju prevoza za: '.date('d.M.Y.', strtotime($beforeUpdate->date)).'. Vaš zahtjev za prevozom je obrisan!';
            sendNotifications($title, $message, $offer_regs_for_deleted_requests, $search, 'search');
		}

		// check matched offers
		$offers = $this->getOfferMatches($search);
		if (!$offers)
            exit(json_encode(array()));

        $resp = ['search_id' => $search->id, 'offers' => $offers['offers']];
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);

		// notification to offerers
		$title = 'Izmjena potražnje prevoza';
		$message = $user.' je objavio da traži prevoz, koji se podudara sa vašom ponudom.';
		sendNotifications($title, $message, $offers['regs'], $search, 'search');

		/*
		if success
            search_id, offers []
		else
            message...
		*/
	}

	public function getOfferMatches($search){
		$offers = Offer::getMatches($search);
		$regs = array();

		foreach ($offers as $offer) {
			$offer->user = User::findSpecific($offer->user_id);
			$offer->date = date('d.M.Y.', strtotime($offer->date));
			$offer->time = substr($offer->time, 0, 5).'h';

			$route_array = explode(' - ', $offer->route);
			$offer->from = $route_array[0];
			$offer->to = end($route_array);
			unset($offer->user_id, $offer->route);

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