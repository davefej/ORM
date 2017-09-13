<?php
class User extends MySerializable{
	
	
	public function __construct(){		
		$this->attributes = array(
				"id" => 0,
				"username" => "",
				"password" => "",				
				"ip" => "",
				"email" => "",
				"created" => (new DateTime()),
				"deleted" => false
		);		
	}
	
	public static function name(){
		return "user";
	}
	
 }
 
?>