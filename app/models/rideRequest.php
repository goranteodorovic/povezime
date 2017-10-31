<?php

namespace App\Models;

use App\Models\Common;
use App\Models\User;
use App\Models\Reg;
use App\Models\Search;
use App\Models\Offer;

Class RideRequest extends Common {
	protected $table = 'requests';

	protected $fillable = ['user_id', 'type', 'search_id', 'offer_id', 'answer', 'updated_at'];

	public function deleteRequest(){

		$type = $this->type;
		$answer = $this->answer;
		$search_id = $this->search_id;
		$offer_id = $this->offer_id;

		$offer = Offer::where('id', $offer_id)->first();
		$search = Search::where('id', $search_id)->first();

		$response = ['success' => 1];
		$response['user'] = User::fullName($this->user_id);

		$this->deleteRecord();

		if ((!$search && $type == 'offer') || (!$offer && $type == 'search'))
			return $response;
		
		if ($answer == 'accepted') {
			$offer->updateRecord(['seats' => $offer->seats + $search->seats_start]);
			$search->updateRecord(['seats' => $search->seats_start]);
			
			if ($type == 'search'){
				$response['regs'] = Reg::where('user_id', $offer->user_id)->pluck('reg_id')->all();
				$response['object'] = Search::find($search_id);
				$response['type'] = 'search';
			} else {
				$response['regs'] = Reg::where('user_id', $search->user_id)->pluck('reg_id')->all();
				$response['object'] = Offer::find($offer_id);
				$response['type'] = 'offer';
			}
		}

		return $response;

		/*
		if success
			success: 1
			regs
			object
			type
		else
			success: 0
			message...
		*/
	}

}