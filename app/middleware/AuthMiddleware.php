<?php
namespace App\Middleware;

class AuthMiddleware
{
    public function __invoke($request, $response, $next)
    {
        $authHeader = $request->getHeader('authorization');
        if (!isset($authHeader[0]))
            displayMessage('Neovlašten pristup!!!', 401);

        try {
            global $container;
            $key = $container->get('settings')['jwtKey'];

            $jwt = substr($authHeader[0], strpos($authHeader[0], 'Bearer') + 7);
            //\Firebase\JWT\JWT::decode($jwt, $key, array('HS256'));

            $found = \App\Models\Token::select('id', 'user_id', 'token')
                ->where('token', $jwt)
                //->where('user_id', $request->getParam('user_id'))
                ->get();

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