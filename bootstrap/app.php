<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/firebase/firebase.php';
require dirname(__DIR__) . '/app/firebase/push.php';

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\SlimException as Exception;

// Slim app setup
$app = new Slim\App([
	'settings' => [
		'displayErrorDetails' => false,
		'determineRouteBeforeAppMiddleware' => true,
		'addContentLengthHeader' => false,

		// eloquent setup
		'db' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'povezime',
            'username' => 'root',
            'password' => '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]
	]
]);

// container create
$container = $app->getContainer();

// eloquent setup
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// eloquent registration
$container['db'] = function($container) use ($capsule){
    return $capsule;
};

// flash messages registration
$container['flash'] = function() {
    return new \Slim\Flash\Messages();
};

// view registration
$container['view'] = function($container){
    $view = new \Slim\Views\Twig(dirname(_FILE_) . '/../views', [
        'cache' => false
    ]);
    
    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

    // setting flash as global
    $view->getEnvironment()->addGlobal('flash', $container->flash);

    return $view;
};

$container["errorHandler"] = function ($container) {
    return function (Request $request, Response $response, \Exception $exception) use ($container) {
        $status = $exception->getCode() ?: 500;
        $data = [
            "status" => $status,
            "message" => $exception->getMessage(),
            "file" => $exception->getFile(),
            "line" => $exception->getLine(),
        ];
        return $container['response']
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    };
};

$container['phpErrorHandler'] = function ($container) {
    return function (Request $request, Response $response, \Throwable $error) use ($container) {
        $status = $error->getCode() ?: 500;
        $message = $error->getMessage();
        $data = ['status' => $status, 'message' => $message];
        return $container['response']
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    };
};

$container['notFoundHandler'] = function ($container) {
    return function (Request $request, Response $response) use ($container) {
        $data = [
            'message' => 'Page you are trying to reach does not exist.',
            'page' => $request->getRequestTarget()
        ];
        return $container['response']
            ->withStatus(404)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    };
};

$container['notAllowedHandler'] = function ($container) {
    return function (Request $request, Respose $response, array $methods) use ($container) {
        $status = 400;
        $message = 'Method must be one of: ' . implode(', ', $methods);
        $data = [ "status" => $status, "message" => $message];
        return $container['response']
            ->withStatus(405)
            ->withHeader('Allow', implode(', ', $methods))
            ->withHeader('Content-type', 'text/html')
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    };
};

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// controllers registration
$container['HomeController'] = function($container){
	return new \App\Controllers\HomeController($container);
};
$container['AuthController'] = function($container){
    return new \App\Controllers\Auth\AuthController($container);
};
$container['UserController'] = function($container){
    return new \App\Controllers\Auth\UserController($container);
};
$container['CarController'] = function($container){
    return new \App\Controllers\Auth\CarController($container);
};
$container['OfferController'] = function($container){
    return new \App\Controllers\Rides\OfferController($container);
};
$container['SearchController'] = function($container){
    return new \App\Controllers\Rides\SearchController($container);
};
$container['RideRequestController'] = function($container){
    return new \App\Controllers\Rides\RideRequestController($container);
};

/*unset($container['errorHandler']);
unset($container['phpErrorHandler']);*/

require dirname(__DIR__) . '/app/functions.php';
require dirname(__DIR__) . '/app/routes.php';