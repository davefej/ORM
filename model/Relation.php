<?php
class Relation extends MySerializable{

	private $relationname = "";
	private $definition = array(				
			"owner" => DataTypes::INT,
			"property" => DataTypes::INT
		);
	
	public function definition(){
		return $this->definition;
	}
	
	public function setDefinition($defarray){
		$this->definition = $defarray;
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