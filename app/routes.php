<?php

$app->get('/', 'HomeController:index')->setName('home');

/*-----------------------------------------------------*/
/* User Auth */
$app->post('/user/firebaselogin', 'UserController:firebaseLogin');
$app->post('/user/update', 'UserController:update');

/*-----------------------------------------------------*/
/* Car */
$app->post('/car/new', 'CarController:addNew');
$app->post('/car/update', 'CarController:update');
$app->post('/car/delete', 'CarController:delete');

/*-----------------------------------------------------*/
/* Ride Offers */
$app->post('/offer/ride', 'OfferController:offerRide');
$app->post('/offer/ride/update', 'OfferController:offerRideUpdate');
$app->post('/offer/ride/cancel', 'OfferController:offerRideCancel');

/*-----------------------------------------------------*/
/* Ride Searches */
$app->post('/search/ride', 'SearchController:searchRide');
$app->post('/search/ride/update', 'SearchController:searchRideUpdate');
$app->post('/search/ride/cancel', 'SearchController:searchRideCancel');

/*-----------------------------------------------------*/
/* Requests */
$app->post('/ride/request/all', 'RideRequestController:getAllRequests');
$app->post('/ride/request/cancel', 'RideRequestController:cancelRequest');

$app->post('/offer/ride/request', 'RideRequestController:offerRideRequest');
$app->post('/offer/ride/answer', 'RideRequestController:offerRideAnswer');
$app->post('/search/ride/request', 'RideRequestController:searchRideRequest');
$app->post('/search/ride/answer', 'RideRequestController:searchRideAnswer');