<?php

namespace App\Models;

use App\Models\Common;

Class Search extends Common {
	protected $table = 'searches';

	protected $fillable = ['user_id', 'from', 'to', 'date', 'one_day', 'seats', 'seats_start', 'luggage', 'updated_at'];
}