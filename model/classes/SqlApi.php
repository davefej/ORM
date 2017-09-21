<?php

class SqlApi implements IPersistenceApi{
	
	private $servername = "localhost:3306";
	private $username = "root";
	private $password = "";
	private $dbname = "orm";
	
	
	private function connect(){
		
		// Create connection
		$conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
		// Check connection
		if ($conn->connect_error) {
			
			//TODO LOG CONNErROR  $conn->connect_error
			return false;
		}
		
		if (!$conn->set_charset("utf8")) {
			//TODO charset error	
		
		} 
		return $conn;
	}
	
	private function close($conn){		
		$conn->close();
	}
	
	public function insert(MySerializable $serializable) {
		$attributes_str = "( id,";
		$values_str = "( NULL,";
		$bind_types_str = "";
		$array_of_params = array();
		$data = $serializable->serialize();
		
		$array_of_params[] = &$bind_types_str;
		
		$coma = "";
		foreach($data as $attribute => $value){
			$attributes_str .= $coma.$attribute;
			$values_str .= $coma."?";
			$bind_types_str .= $this->formatType($serializable,$attribute);
			$coma = ",";
		}
		
		$keys = array_keys($data);
		for($i = 0; $i < count($keys); $i++) {			
			$array_of_params[] = & $data[$keys[$i]];
		}
		
		if($serializable->default_deleted()){
			//also insert deleted
			$attributes_str .= ",deleted";
			$values_str .= ",?";
			$bind_types_str .= "i";
			$deleted = (int)$serializable->deleted();
			$array_of_params[] = & $deleted;
		}
		
		$attributes_str = $attributes_str.")";
		$values_str = $values_str.")";
		$SQL = "INSERT INTO ".$serializable->name()." ".$attributes_str." VALUES ".$values_str;
		
		
		
		$conn = $this->connect();
		$stmt = $conn->prepare($SQL);
		if(!$stmt){
			$conn->close();
			return false;
		}
		call_user_func_array(array($stmt, 'bind_param'), $array_of_params);
		if(!$stmt->execute()){
			$stmt->close();
			$conn->close();
			return false;
		}
		$id = $stmt->insert_id;
		$serializable->setId($id);
		$stmt->close();
		$conn->close();
		$serializable = $this->register($serializable);
		return true;
		
	}

	public function update(MySerializable $serializable) {
		$update_str = " ";
		$bind_types_str = "";
		$array_of_params = array();
		$data = $serializable->serialize();
		$changes_keys = $serializable->changed();
		
		$array_of_params[] = &$bind_types_str;		
		$coma = "";
		foreach($data as $attribute => $value){
			if($attribute == "id" || !in_array($attribute, $changes_keys)){
				continue;
			}
			$update_str .= $coma.$attribute."=?";
			$bind_types_str .= $this->formatType($serializable,$attribute);
			$coma = ",";
		}
		
		$keys = array_keys($data);
		for($i = 0; $i < count($keys); $i++) {
			if($keys[$i] == "id" || !in_array($keys[$i], $changes_keys)){
				continue;
			}
			$array_of_params[] = & $data[$keys[$i]];
		}
				
		$SQL = "UPDATE ".$serializable->name()." SET ".$update_str." WHERE id=?";
		
		//ADD filter id to types and params
		$id = $serializable->id();
		$array_of_params[] = & $id;
		$bind_types_str .= "i";
		
		$conn = $this->connect();
		$stmt = $conn->prepare($SQL);
		if(!$stmt){
			$conn->close();
			return false;
		}
		call_user_func_array(array($stmt, 'bind_param'), $array_of_params);
		if(!$stmt->execute()){
			$stmt->close();
			$conn->close();
			return false;
		}
		$stmt->close();
		$conn->close();
		return true;
	}

	public function select($serializableclassname,$sqlfilter) {		
		
		$bind_types_str = "";
		$array_of_params = array();
		$array_of_params[] = &$bind_types_str;
		
		$filter_values = $sqlfilter->values();
		foreach($filter_values as $value){
			$bind_types_str .= $this->formatType2(gettype($value));
		}
		
		$keys = array_keys($filter_values);
		for($i = 0; $i < count($filter_values); $i++) {
			$array_of_params[] = & $filter_values[$keys[$i]];
		}
		
		$SQL = "SELECT * FROM ".$serializableclassname::name()." WHERE ".$sqlfilter->generate();		
		$conn = $this->connect();
		$stmt = $conn->prepare($SQL);
		call_user_func_array(array($stmt, 'bind_param'), $array_of_params);
		
		$stmt->execute();
		
		$meta = $stmt->result_metadata();
		$params = array();
		$row = array();
		$c = array();
		$result = array();
		while ($field = $meta->fetch_field())
		{
			$params[] = &$row[$field->name];
		}
		
		call_user_func_array(array($stmt, 'bind_result'), $params);
		
		while ($stmt->fetch()) {
			foreach($row as $key => $val)
			{
				$c[$key] = $val;
			}
			$result[] = $c;
		}
		$stmt->close();
		$this->close($conn);
		
		$obj_array = array();
		foreach($result as $curr_row){
			$class = new ReflectionClass($serializableclassname);
			$args  = array();
			$serial_obj = $class->newInstanceArgs($args);
			
			$serial_obj = $serial_obj->build($curr_row);
			$serial_obj = $this->register($serial_obj);
			array_push($obj_array, $serial_obj);
		}
		return $obj_array;
	}

	public function delete(MySerializable $serializable) {		
		$SQL = "UPDATE ".$serializable->name()." SET deleted=1 WHERE id=?";
		$conn = $this->connect();
		$stmt = $conn->prepare($SQL);
		if(!$stmt){
			$conn->close();
			return false;
		}
		$stmt->bind_param("i", $serializable->id());
		if(!$stmt->execute()){
			$stmt->close();
			$conn->close();
			return false;
		}
		$stmt->close();
		$conn->close();
		return true;
	}
	
	public function restore($serializable) {		
		$SQL = "UPDATE ".$serializable->name()." SET deleted=0 WHERE id=?";
		$conn = $this->connect();
		$stmt = $conn->prepare($SQL);
		if(!$stmt){
			$conn->close();
			return false;
		}
		$stmt->bind_param("i", $serializable->id());
		if(!$stmt->execute()){
			$stmt->close();
			$conn->close();
			return false;
		}
		$stmt->close();
		$conn->close();
		return true;
	}
	
	private function formatType(MySerializable $serializable, $attribute){
		$type = $serializable->definition()[$attribute];
		switch ($type){
			case DataTypes::STRING:
				return "s";
			case DataTypes::INT:
				return "i";
			case DataTypes::DATE:
				return "i";
			case DataTypes::BOOL:
				return "i";
			default:
				if(is_subclass_of($type,MySerializable::class)){
					return "i";
				}else{
					throw new Exception('Not implemented');
				}		
		}
	}
	
	private function formatType2($type){
		switch ($type){
			case "string":
				return "s";
			case "integer":
				return "i";
			case "NULL":
				return "i";
			default:
				throw new Exception('Unimplemented format type');
		}
	}
	

	public static function getInstance()
	{
		static $inst = null;
		if ($inst === null) {
			$inst = new SqlApi();
		}
		return $inst;
	}
	
	private function __construct()
	{
	
	}
	
	private function register(MySerializable $obj){
		$o = ObjectRegistry::getInstance();
		return $o->register($obj);
	}
	
	private function inRegistry($class, $id){
		return ObjectRegistry::getInstance()->inRegistry($class, $id);		
	}
	
	private function getFromRegistry($class, $id){
		return ObjectRegistry::getInstance()->getFromRegistry($class, $id);
	}

}



?>