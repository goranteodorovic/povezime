<?php

namespace App\Controllers\Auth;

use App\Controllers\Controller;

use App\Models\User;
use App\Models\Car;

Class CarController extends Controller {
	
	public function addNew($request, $response){
	//	required: user_id
	//	optional: make, model, seats, image
		$params = $request->getParams();
		$required = ['user_id'];
		checkRequiredFields($required, $params);

		$response = ['success' => 1];

		$user = User::find($params['user_id']);
		if(!$user)
			displayMessage('Pogrešan id.');

		$car = Car::create($params);
		if(!$car->id)
			displayMessage('Spremanje automobila neuspješno.');

		$response['car'] = $car;
		echo json_encode($response, JSON_UNESCAPED_UNICODE);

		/*
		if success
			success: 1
			car
		else
			success: 0
			message...
		*/
	}

	public function update($request, $response){
	//	required: id
	//	optional: make, model, seats, image
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		$response = ['success' => 1];

		$car = Car::find($params['id']);
		if(!$car)
			displayMessage('Traženi automobil ne postoji u bazi.');

		$car->updateRecord($params);
		$response['car'] = Car::find($params['id']);
		echo json_encode($response, JSON_UNESCAPED_UNICODE);

		/*
		if success
			success: 1
			car
		else
			success: 0
			message...
		*/
	}

	public function delete($request, $response){
	// 	required: id
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		$response = ['success' => 1];

		$car = Car::find($params['id']);
		if(!$car)
			displayMessage('Traženi automobil ne postoji u bazi.');

		if (!$car->deleteRecord())
			displayMessage('Došlo je do greške...');

		return json_encode($response, JSON_UNESCAPED_UNICODE);

		/*
		if success
			success: 1
		else
			success: 0
			message...
		*/
	}

}