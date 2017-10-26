<?php

namespace App\Controllers;

Class Controller {
	protected $container;

	public function __construct($container){
		$this->container = $container;
	}

	// check if property exists in container
	public function __get($property){
	    if($this->container->{$property}){
	        return $this->container->{$property};
	    }
	}
}