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
	
	public function insert($serializable) {		
		$attributes_str = "( id,";
		$values_str = "( NULL,";
		$bind_types_str = "";
		$array_of_params = array();
		$data = $serializable->seriaize();
		
		$array_of_params[] = &$bind_types_str;
		
		$coma = "";
		foreach($data as $attribute => $value){
			$attributes_str .= $coma.$attribute;
			$values_str .= $coma."?";
			$bind_types_str .= $this->formatType(gettype($value));
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
		return true;
		
	}

	public function update($serializable) {
		$update_str = " ";
		$bind_types_str = "";
		$array_of_params = array();
		$data = $serializable->seriaize();
		$changes_keys = $serializable->changed();
		
		$array_of_params[] = &$bind_types_str;		
		$coma = "";
		foreach($data as $attribute => $value){
			if($attribute == "id" || !in_array($attribute, $changes_keys)){
				continue;
			}
			$update_str .= $coma.$attribute."=?";
			$bind_types_str .= $this->formatType(gettype($value));
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

	public function select($serializableclass,$sqlfilter) {
		
		$bind_types_str = "";
		$array_of_params = array();
		$array_of_params[] = &$bind_types_str;
		
		$filter_values = $sqlfilter->values();
		foreach($filter_values as $value){
			$bind_types_str .= $this->formatType(gettype($value));
		}
		
		$keys = array_keys($filter_values);
		for($i = 0; $i < count($filter_values); $i++) {
			$array_of_params[] = & $filter_values[$keys[$i]];
		}
		
		$SQL = "SELECT * FROM ".$serializableclass::name()." WHERE ".$sqlfilter->generate();		
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
			$class = new ReflectionClass($serializableclass);
			$args  = array();
			$serial_obj = $class->newInstanceArgs($args);
			
			$serial_obj->build($curr_row);
			array_push($obj_array, $serial_obj);
		}
		return $obj_array;
	}

	public function delete($serializable) {		
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
	
	private function formatType($type){
		switch ($type){
			case "string":
				return "s";
			case "integer":
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

}

class SqlFilter{
	
	private $filter_str = "";
	private $orderby = "";
	private $values = array();
	
	public function reset(){
		$this->filter_str = "";
		$this->values = array();
	}
	
	public function addand($attribute,$operator,$value){
		if($this->filter_str != ""){
			$this->filter_str .= " AND ";
		}
		$this->filter_str .= $attribute.=" ".$operator." ?";
		array_push($this->values, $value);
	}
	
	public function addor($attribute,$operator,$value){
		if($this->filter_str != ""){
			$this->filter_str .= " OR ";
		}
		$this->filter_str .= $attribute.=" ".$operator." ?";
		array_push($this->values, $value);
	}
	
	public function generate(){
		if($this->filter_str == ""){
			return " 1=? ".$this->orderby;
		}else{
			return $this->filter_str.$this->orderby;
		}
		
	}
	
	public function values(){
		if($this->filter_str == ""){
			return array(1);
		}else{
			return $this->values;
		}
		
	}
	
	public function orderby($str){
		$this->orderby = " ORDER BY ".$str." ";
	}
	
	public function reverseorderby($str){
		$this->orderby = " ORDER BY ".$str." DESC ";
	}
}

?>