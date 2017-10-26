<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/firebase/firebase.php';
require dirname(__DIR__) . '/app/firebase/push.php';

// Slim app setup
$app = new Slim\App([
	'settings' => [
		'displayErrorDetails' => true,
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

require dirname(__DIR__) . '/app/functions.php';
require dirname(__DIR__) . '/app/routes.php';