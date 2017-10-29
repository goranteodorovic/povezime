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
$app->post('/ride/offer', 'OfferController:rideOffer');
//$app->post('/ride/offer/cancel', 'OfferController:cancelOffer');

/*-----------------------------------------------------*/
/* Ride Searches */
$app->post('/ride/search', 'SearchController:rideSearch');
//$app->post('/ride/search/cancel', 'SearchController:cancelSearch');

/*-----------------------------------------------------*/
/* Requests */
$app->post('/ride/request/all', 'RideRequestController:getAllRequests');
$app->post('/ride/request/cancel', 'RideRequestController:cancelRequest');

$app->post('/offer/ride/request', 'RideRequestController:offerRideRequest');
$app->post('/offer/ride/answer', 'RideRequestController:offerRideAnswer');
$app->post('/search/ride/request', 'RideRequestController:searchRideRequest');
$app->post('/search/ride/answer', 'RideRequestController:searchRideAnswer');