<?php

interface IObjectRegistry{

	
	public function register(MySerializable $obj);
	
	public function inRegistry($class, $id);
	
	public function getFromRegistry($class, $id);
	
	public static function getInstance();
	
	public function log();
	
}