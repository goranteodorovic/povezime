<?php

namespace App\Models;

use App\Models\Common;

Class Offer extends Common {
	protected $table = 'offers';

	protected $fillable = ['user_id', 'route', 'car_id', 'seats', 'seats_start', 'date', 'time', 'luggage', 'updated_at'];

	// 29.10.
	public static function getMatches($search){

		$offers = Offer::select('id', 'user_id', 'route', 'date', 'time', 'seats', 'luggage')
		->where('seats', '>', 0)
		->where('seats', '>=', $search->seats);
		// filter by luggage
		if($search->luggage == 1){ $offers = $offers->where('luggage', 1); }
		// filter by date
		if($search->one_day == 0){ $offers = $offers->where('date', $search->date); } 
		else {
			$plus_one = date('Y-m-d', strtotime("+1 day", strtotime($search->date)));
			$minus_one = date('Y-m-d', strtotime("-1 day", strtotime($search->date)));
			$offers = $offers->whereBetween('date', [$minus_one, $plus_one]);
		}

		$offers = $offers->get(); // array of objects

		// lat long of search object
		$from_lat = substr($search->from, 0, strpos($search->from, ','));
		$from_long = substr($search->from, strpos($search->from, ',')+1);

		$to_lat = substr($search->to, 0, strpos($search->to, ','));
		$to_long = substr($search->to, strpos($search->to, ',')+1);

		$from = null;		// closest place from SEARCH->FROM to offer route array
		$to = null;			// closest place from SEARCH->TO to offer route array

		//$offer_regs = array();	

		foreach($offers as $index => $offer){
			$offer_route_arr = explode(" - ", $offer->route);

			foreach($offer_route_arr as $latlong){
				$lat = substr($latlong, 0, strpos($latlong, ','));
				$long = substr($latlong, strpos($latlong, ',')+1);

				// get distances between each latlong and search from/to latlong
				// if distance is less than 5km save latlong for further check
				$distance_from = getDistance($from_lat, $from_long, $lat, $long);
				$distance_to = getDistance($to_lat, $to_long, $lat, $long);

				if($distance_from < 5)	{	$from = $latlong;	}
				if($distance_to < 5)	{	$to = $latlong;		}
			}

			// filter result on saved latlong and direction
			if(!isset($from) || !isset($to) || strpos($offer->route, $from) > strpos($offer->route, $to))
				unset($offers[$index]);
		}

		if (!empty($offers))
			return $offers;
		else
			return false;
	}
}