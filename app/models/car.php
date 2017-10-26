<?php

namespace App\Models;

use App\Models\Common;

Class Car extends Common {
	protected $table = 'cars';

	protected $fillable = ['user_id', 'make', 'model', 'seats', 'image', 'updated_at'];
}