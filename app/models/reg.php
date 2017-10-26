<?php

namespace App\Models;

use App\Models\Common;

Class Reg extends Common {
	protected $table = 'regs';

	protected $fillable = ['user_id', 'reg_id', 'updated_at'];
}