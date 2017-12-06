<?php

namespace App\Models;

use App\Models\Common;

Class Offer extends Common {
	protected $table = 'offers';

	protected $fillable = ['user_id', 'route', 'car_id', 'seats', 'seats_start', 'date', 'time', 'luggage', 'updated_at'];

    public static function findById($id){
        $offer = self::find($id);
        if (!$offer)
            return false;

        $route_array = explode(' - ', $offer->route);
        $offer->from = $route_array[0];
        $offer->to = end($route_array);

        unset($offer->created_at, $offer->updated_at);
        return $offer;
    }

	public static function getMatches($search){

		$offers = Offer::select('id', 'user_id', 'route', 'date', 'time', 'seats', 'luggage')
		->where('seats', '>', 0)
		->where('seats', '>=', $search->seats);
		// filter by luggage
		if($search->luggage === 1){ $offers = $offers->where('luggage', 1); }
		// filter by date
		if($search->one_day === 0){ $offers = $offers->where('date', $search->date); }
		else {
			$plus_one = date('Y-m-d', strtotime("+1 day", strtotime($search->date)));
			$minus_one = date('Y-m-d', strtotime("-1 day", strtotime($search->date)));
			$offers = $offers->whereBetween('date', [$minus_one, $plus_one]);
		}

		$offers = $offers->get(); // array of objects

		$from = null;		// closest place from SEARCH->FROM to offer route array
		$to = null;			// closest place from SEARCH->TO to offer route array

		foreach($offers as $index => $offer){
			$offer_route_arr = explode(" - ", $offer->route);

			foreach($offer_route_arr as $latlong){
				// if distance is less than 5km save latlong for further check
				$distance_from = getDistanceGeoKit($latlong, $search->from);
                $distance_to = getDistanceGeoKit($latlong, $search->to);

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