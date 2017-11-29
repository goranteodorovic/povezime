<?php

namespace App\Controllers\Auth;

use App\Controllers\Controller;

use App\Models\User;
use App\Models\Reg;
use App\Models\Car;

Class UserController extends Controller {
	
	public function firebaseLogin($request, $response){
	//	required:	email, reg_id
		$params = $request->getParams();
		$required = ['email', 'reg_id'];
		checkRequiredFields($required, $params);

		$user = User::where('email', $params['email'])->first();
		if (!$user) {
			$user = User::create($params);
			if(!$user->id)
				displayMessage('Spremanje korisnika neuspješno.', 503);
			$user_id = $user->id;
		} else
			$user_id = $user->id;

        //$resp['user'] = &$user;

		// inserting reg id into db
		$found_reg = Reg::where('reg_id', $params['reg_id'])->first();
		if (!$found_reg) {
			$reg = Reg::create(['user_id' => $user_id, 'reg_id' => $params['reg_id']]);
			if (!$reg->id)
				displayMessage('Spremanje reg id-a neuspješno.', 503);
		} else {
			if ($found_reg->user_id != $user->id)
				displayMessage('Registracija nije dozvoljena.', 403);
		}

        $user->cars = Car::getAllByUserId($user_id);
		$user->regs = Reg::where('user_id', $user_id)->pluck('reg_id')->all();
		echo json_encode($user, JSON_UNESCAPED_UNICODE);

		/*
		if success
			user
		else
			messsage
		*/
	}

	public function update($request, $response){
	//	required:	id
	//	optional:	name, surname, phone, viber, whatsapp, image
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		$user = User::find($params['id']);
		if(!$user)
			displayMessage('Pogrešan id.', 403);

		$user->updateRecord($params);
		$user->cars = Car::getAllByUserId($user->id);
        $user->regs = Reg::where('user_id', $user->id)->pluck('reg_id')->all();

        //$resp['user'] = $user;
		echo json_encode($user, JSON_UNESCAPED_UNICODE);

		/*
		if success
			user
		else
			messsage
		*/
	}
}