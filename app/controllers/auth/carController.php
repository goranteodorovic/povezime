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

        $resp['car'] = $car;
		echo json_encode($resp, JSON_UNESCAPED_UNICODE);

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
		$resp['car'] = Car::find($params['id']);
		echo json_encode($resp, JSON_UNESCAPED_UNICODE);

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

		if (!$car->deleteRecord())
			displayMessage('Brisanju automobila neuspješno.', 503);

        $resp = ['success' => 1];
        return json_encode($resp, JSON_UNESCAPED_UNICODE);

		/*
		if success
			success 1
		else
			message...
		*/
	}

}