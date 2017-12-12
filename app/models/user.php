<?php

namespace App\Models;

use App\Models\Common;

Class User extends Common {
	protected $table = 'users';

	protected $fillable = ['name', 'surname', 'email', 'phone', 'viber', 'whatsapp', 'image', 'password', 'updated_at'];

	public static function findSpecific($id){
	    $user = self::find($id);
	    unset($user->created_at, $user->updated_at);
	    return $user;
    }

	public static function fullName($user_id){
		$user = self::find($user_id);

		if($user->name && $user->surname)
			return $user->name.' '.$user->surname;

		elseif($user->email)
			return $user->email;
			
		else return false;
	}
}