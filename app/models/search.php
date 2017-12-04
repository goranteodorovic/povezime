<?php

namespace App\Models;

use App\Models\Common;

Class Search extends Common {
	protected $table = 'searches';

	protected $fillable = ['user_id', 'from', 'to', 'date', 'one_day', 'seats', 'seats_start', 'luggage', 'updated_at'];

	public static function getMatches($offer){
	//  returns $searches / false		
		$offer_route_arr = explode(" - ", $offer->route);

		$plus_one = date('Y-m-d', strtotime("+1 day", strtotime($offer->date)));
		$minus_one = date('Y-m-d', strtotime("-1 day", strtotime($offer->date)));

		// filter by seats, partly-date
		$searches = self::select('id', 'user_id', 'from', 'to', 'date', 'one_day', 'seats', 'luggage')
		->where('seats', '>', 0)
		->where('seats', '<=', $offer->seats)
		->whereBetween('date', [$minus_one, $plus_one]);
		if($offer->luggage === 0){ $searches = $searches->where('luggage', 0); }
		$searches = $searches->get();

		foreach ($searches as $index => $search) {
			if ($search->one_day === 0 && $offer->date !== $search->date)
				unset($searches[$index]);

			$from = null;		// closest place from SEARCH->FROM to offer route array
            $to = null;			// closest place from SEARCH->TO to offer route array

            // get distances between each latlong and offer from/to latlong
			foreach ($offer_route_arr as $latlong) {
                // if distance is less than 5km save latlong for further check
				$distance_from = getDistanceGeoKit($latlong, $search->from);
                $distance_to = getDistanceGeoKit($latlong, $search->to);

				if($distance_from < 5)	{	$from = $latlong;	}
				if($distance_to < 5)	{	$to = $latlong;		}
			}

			// filter result on saved latlong and direction
			if(!isset($from) || !isset($to) || strpos($offer->route, $from) > strpos($offer->route, $to))
				unset($searches[$index]);
		}

		if (!empty($searches))
			return $searches;
		else
			return false;
	}
}