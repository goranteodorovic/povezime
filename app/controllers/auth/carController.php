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

		$response = ['success' => 1];

		$car = Car::find($params['id']);
		if(!$car)
			displayMessage('Traženi automobil ne postoji u bazi.');

		$car->updateRecord($params);
		$response['record'] = Car::find($params['id']);
		echo json_encode($response, JSON_UNESCAPED_UNICODE);

		/*
		if success
			success: 1
			record
		else
			success: 0
			message...
		*/
	}

	public function delete($request, $response){
	/* 	required: id */
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