<?php

namespace App\Models;

use App\Models\Common;

class Token extends Common {
    protected $table = 'tokens';

    protected $fillable = ['user_id', 'token', 'updated_at'];

}