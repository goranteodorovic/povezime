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

        $jwt = $this->tokenGenerate($user->id, $user->email);
        $user->token = $jwt;

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
			user (obj)
		else
			messsage
		*/
	}

	public function logout($request, $response){
        $params = $request->getParams();
        $required = ['user_id', 'reg_id'];
        checkRequiredFields($required, $params);

        $user = User::find($params['user_id']);
        if(!$user)
            displayMessage('Pogrešan id.', 403);

        $regId = Reg::find($params['reg_id']);
        if ($regId)
            $regId->deleteRecord();

        echo $params['reg_id'];
        //echo json_encode(['reg_id'=>$params['reg_id']]);
    }

	public function update($request, $response){
	//	required:	user_id
	//	optional:	name, surname, phone, viber, whatsapp, image
		$params = $request->getParams();
		$required = ['user_id'];
		checkRequiredFields($required, $params);

		$user = User::find($params['user_id']);
		if(!$user)
			displayMessage('Pogrešan id.', 403);

		$user->updateRecord($params);

        echo json_encode($user, JSON_UNESCAPED_UNICODE);

		/*
		if success
			user (obj)
		else
			messsage
		*/
	}

	public function validate($request, $response){
	//  required: token (header)
        $authHeader = $request->getHeader('authorization');
        if (!isset($authHeader[0]))
            displayMessage('Neovlašten pristup!!!', 401);

        $jwt = substr($authHeader[0], strpos($authHeader[0], 'Bearer') + 7);
        $token = \App\Models\Token::select('id', 'user_id', 'token')
            ->where('token', $jwt)
            ->get();

        if (count($token) == 0)
            displayMessage('Neovlašten pristup!!!', 401);

        $user = User::findSpecific($token->user_id);
        if (!$user)
            displayMessage('Nemoguće pronaći korisnika na osnovu tokena!', 403);

        $user->cars = Car::getAllByUserId($user->id);
        $user->regs = Reg::where('user_id', $user->id)->pluck('reg_id')->all();

        echo json_encode($user, JSON_UNESCAPED_UNICODE);

        /*
		if success
			user (obj)
		else
			messsage
		*/
    }

    public function tokenGenerate($user_id, $email) {
        global $container;
        $key = $container->get('settings')['jwtKey'];
        $token = array(
            "iss" => "povezime.app",
            "iat" => time(),
            "data" => [
                "user_id" => $user_id,
                "email" => $email
            ]
        );

        $found = \App\Models\Token::where('user_id', $user_id)->first();
        if (count($found) > 0)
            return 'Bearer '.$found->token;

        $jwt = \Firebase\JWT\JWT::encode($token, $key);
        $token = \App\Models\Token::create(['user_id'=>$user_id, 'token'=>$jwt]);
        if (!$token->id)
            displayMessage('Spremanje tokena neuspješno.', 503);

        $jwt = 'Bearer '.$jwt;
        return $jwt;
    }
}