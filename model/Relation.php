<?php
class Relation extends MySerializable{

	private $relationname = "";
	
	
	public function definition(){
		return array(				
			"owner" => DataTypes::INT,
			"property" => DataTypes::INT
		);
	}

	public static function name(){
		return "";
	}

	public function setRelationName($relationname){
		$this->relationname = $relationname;
	}
		
	public function getRelationName(){
		return $this->relationname;
	}
	
}

?>