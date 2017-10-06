<?php
class User extends MySerializable{
	
	public function definition(){		
		return array(
				"id" => DataTypes::INT,
				"username" => DataTypes::STRING,
				"password" => DataTypes::STRING,
				"ip" => DataTypes::STRING,
				"email" => DataTypes::STRING,
				"created" => DataTypes::DATE,
				"father" => User::dataType(),
				"children" => User::arrayType(),
				"eyes" => Eye::arrayType(),
				"deleted" => DataTypes::BOOL
		);
	}	
	
	public static function name(){
		return "user";
	}
	
 }
 
?>