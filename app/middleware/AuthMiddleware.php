<?php
namespace App\Middleware;

class AuthMiddleware
{
    public function __invoke($request, $response, $next)
    {
        $authHeader = $request->getHeader('authorization');

        try {
            $jwt = substr($authHeader[0], strpos($authHeader[0], 'Bearer') + 7);
            \Firebase\JWT\JWT::decode($jwt, 'jwtKey', array('HS256'));

            $found = \App\Models\Token::select('id', 'user_id', 'token')->where('token', $jwt)->get();

            if (count($found) == 0)
                displayMessage('Neovlašten pristup!!!', 401);

            $response = $next($request, $response);
            return $response;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            displayMessage($message, 401);
        }
    }
}