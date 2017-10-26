<?php

namespace App\Controllers\Auth;

use App\Controllers\Controller;

use App\Models\User;
use App\Models\Reg;
use App\Models\Car;

Class UserController extends Controller {
	// user login
	public function firebaseLogin($request, $response){
	/*	required:	email, reg_id */
		$params = $request->getParams();
		$required = ['email', 'reg_id'];
		checkRequiredFields($required, $params);

		$user = User::where('email', $params['email'])->first();
		if(!$user){
			$user = User::create($params);
			if(!$user->id)
				displayMessage('Spremanje korisnika neuspješno.');
			$user_id = $user->id;
		} else
			$user_id = $user->id;

		// inserting reg id into db
		$found_reg = Reg::where('reg_id', $params['reg_id'])->first();
		if(!$found_reg){
			$reg = Reg::create(['user_id' => $user_id, 'reg_id' => $params['reg_id']]);
			if(!$reg->id)
				displayMessage('Spremanje reg id-a neuspješno.');
		} else {
			if($found_reg->email != $user->email)
				displayMessage('Registracija nije dozvoljena.');
		}

		// creating response
		$response = array();

		$collection = collect($user);
		foreach($collection as $key => $value){
			if($key != 'created_at' && $key != 'updated_at'){
				$response[$key] = $value;
			}
		}

		$response['cars'] = Car::getAllByUserId($user_id);

		$regs = Reg::where('user_id', $user_id)->pluck('reg_id')->all();
		$response['regs'] = $regs;

		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}

	public function update($request, $response){
	/*	required:	id
		optional:	name, surname, phone, viber, whatsapp, image */
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		$response = array();

		$user = User::find($params['id']);
		if(!$user)
			displayMessage('Pogrešan id.');

		$updated = $user->updateRecord($params);
		if(!$updated)
			displayMessage('Izmjena podataka neuspješna.');

		unset($updated->created_at, $updated->updated_at);
		echo json_encode($updated, JSON_UNESCAPED_UNICODE);
	}

	public function delete($request, $response){
		//
	}
	
}