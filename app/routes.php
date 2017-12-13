<?php

$app->get('/', 'HomeController:index')->setName('home');

/*-----------------------------------------------------*/
/* User Auth */
$app->post('/user/firebaselogin', 'UserController:firebaseLogin');

$app->group('', function(){
    $this->post('/user/update', 'UserController:update');

    /*-----------------------------------------------------*/
    /* Car */
    $this->post('/car/new', 'CarController:addNew');
    $this->post('/car/update', 'CarController:update');
    $this->post('/car/delete', 'CarController:delete');

    /*-----------------------------------------------------*/
    /* Ride Offers */
    $this->post('/offer/ride', 'OfferController:offerRide');
    $this->post('/offer/ride/update', 'OfferController:offerRideUpdate');
    $this->post('/offer/ride/cancel', 'OfferController:offerRideCancel');

    /*-----------------------------------------------------*/
    /* Ride Searches */
    $this->post('/search/ride', 'SearchController:searchRide');
    $this->post('/search/ride/update', 'SearchController:searchRideUpdate');
    $this->post('/search/ride/cancel', 'SearchController:searchRideCancel');

    /*-----------------------------------------------------*/
    /* Requests */
    $this->post('/ride/request/all', 'RideRequestController:getAllRequests');
    $this->post('/ride/request/cancel', 'RideRequestController:cancelRequest');

    $this->post('/offer/ride/request', 'RideRequestController:offerRideRequest');
    $this->post('/offer/ride/answer', 'RideRequestController:offerRideAnswer');
    $this->post('/search/ride/request', 'RideRequestController:searchRideRequest');
    $this->post('/search/ride/answer', 'RideRequestController:searchRideAnswer');

    $this->post('/test', function() {
        echo 'TEST';
    });

})->add(new App\Middleware\AuthMiddleware);