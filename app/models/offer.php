<?php

namespace App\Models;

use App\Models\Common;

Class Offer extends Common {
	protected $table = 'offers';

	protected $fillable = ['user_id', 'route', 'car_id', 'seats', 'seats_start', 'date', 'time', 'luggage', 'updated_at'];
}