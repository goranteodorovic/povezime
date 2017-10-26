<?php

namespace App\Controllers\Auth;

use App\Controllers\Controller;

use App\Models\User;
use App\Models\Car;

Class CarController extends Controller {
	
	public function addNew($request, $response){
	/*	required: user_id
		optional: make, model, seats, image */
		$params = $request->getParams();
		$required = ['user_id'];
		checkRequiredFields($required, $params);

		$user = User::find($params['user_id']);
		if(!$user)
			displayMessage('Pogrešan id.');

		$car = Car::create($params);
		if(!$car->id)
			displayMessage('Spremanje automobila neuspješno.');

		echo json_encode($car, JSON_UNESCAPED_UNICODE);
	}

	public function update($request, $response){
	/*	required: id
		optional: make, model, seats, image */
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		$car = Car::find($params['id']);
		if(!$car)
			displayMessage('Traženi automobil ne postoji u bazi.');

		$updated = $car->updateRecord($params);
		if (!$updated)
		 	displayMessage('Izmjena podataka neuspješna.');

		echo json_encode($updated, JSON_UNESCAPED_UNICODE);
	}

	public function delete($request, $response){
	/* 	required: id */
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		$car = Car::find($params['id']);
		if(!$car)
			displayMessage('Traženi automobil ne postoji u bazi.');

		if ($car->deleteRecord())
			echo '{"success":1}';
		else
			echo '{"success":0, "message":"Došlo je do greške..."}';
	}

}