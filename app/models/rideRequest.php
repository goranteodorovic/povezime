<?php

namespace App\Models;

use App\Models\Common;

Class RideRequest extends Common {
	protected $table = 'requests';

	protected $fillable = ['user_id', 'type', 'search_id', 'offer_id', 'answer', 'updated_at'];

}