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

		$user = User::find($params['user_id']);
		if(!$user)
			displayMessage('Pogrešan id.', 400);

		$car = Car::create($params);
		if(!$car->id)
			displayMessage('Spremanje automobila neuspješno.', 503);

        echo json_encode($car, JSON_UNESCAPED_UNICODE);

		/*
		if success
			car
		else
			message...
		*/
	}

	public function update($request, $response){
	//	required: id
	//	optional: make, model, seats, image
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		$car = Car::find($params['id']);
		if(!$car)
			displayMessage('Traženi automobil ne postoji u bazi.', 400);

		$car->updateRecord($params);

		echo json_encode($car, JSON_UNESCAPED_UNICODE);

		/*
		if success
			car
		else
			message...
		*/
	}

	public function delete($request, $response){
	// 	required: id
		$params = $request->getParams();
		$required = ['id'];
		checkRequiredFields($required, $params);

		$car = Car::find($params['id']);
		if(!$car)
			displayMessage('Traženi automobil ne postoji u bazi.', 400);

        $car->deleteRecord();
        echo json_encode(['id'=>$params['id']]);

		/*
		if success
			id
		else
			message...
		*/
	}

}