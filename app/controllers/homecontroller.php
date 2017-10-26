<?php

namespace App\Controllers;

Class HomeController extends Controller {
	// renderuje view
    public function index($request, $response){
    	echo '<h1>Welcome, guest!</h1>';
        echo '<p>This is only a back-end for the app that is still in progress of making...</p>
              <p>If you are eager to check how this back-end works, before the actual app is done, you\'ll need to use Postman.</p>';

        echo '<p>If you have any questins, you can contact me via email:<br>';

        echo '<a href="mailto:goran.teodorovic@rocketmail.com">goran.teodorovic@rocketmail.com</a><br>
                or via facebook:<br> <a href="https://www.facebook.com/goran.teodorovic1988">Goran Teodorović</a></p>';
    }
}
