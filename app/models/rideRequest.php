<?php

namespace App\Models;

use App\Models\Common;
use App\Models\User;
use App\Models\Reg;
use App\Models\Search;
use App\Models\Offer;

/*
 * izmijenio type i answer kolonu
 */

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

		// return true or exit(msg)
		$this->deleteRecord();

		if ((!$search && $type == 'O') || (!$offer && $type == 'S'))
		    return true;
		
		if ($answer == 'A') {
			$offer->updateRecord(['seats' => $offer->seats + $search->seats_start]);
			$search->updateRecord(['seats' => $search->seats_start]);

            if ($type == 'S')
                $regs = Reg::where('user_id', $offer->user_id)->pluck('reg_id')->all();
            else
                $regs = Reg::where('user_id', $search->user_id)->pluck('reg_id')->all();

            return $regs;
		}

		/*
		if success
			regs
		else
			message...
		*/
	}

}

/*
 * 33, 36, 40
 */