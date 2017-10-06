<?php
class Eye extends MySerializable{
	
	public function definition(){		
		return array(
			"id" => DataTypes::INT,				
			"color" => DataTypes::STRING,
			"user" => User::dataType(),
			"isright" => DataTypes::BOOL,
			"deleted" => DataTypes::BOOL
		);		
	}	
	
	public static function name(){
		return "eye";
	}
	
 }
 
?>