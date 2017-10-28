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
			throw new Exception("Can't connect to database".$conn->connect_error);
		}
		
		if (!$conn->set_charset("utf8")) {
			throw new Exception("Can't load carset utf-8");
		} 
		return $conn;
	}
	
	private function close($conn){		
		$conn->close();
	}
	
	public function insert(MySerializable $serializable) {
		$this->manyRelationSave($serializable);
		$attributes_str = "( id,";
		$values_str = "( NULL,";
		$bind_types_str = "";
		$array_of_params = array();
		$data = $serializable->serialize();
		
		$array_of_params[] = &$bind_types_str;
		
		$coma = "";
		$attributes = array_keys($data);
		foreach($attributes as $attribute){
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
		$this->manyRelationSave($serializable);
		$update_str = " ";
		$bind_types_str = "";
		$array_of_params = array();
		$data = $serializable->serialize();
		$changes_keys = $serializable->changed();
		
		$array_of_params[] = &$bind_types_str;		
		$coma = "";
		$attributes = array_keys($data);
		foreach($attributes as $attribute){
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
			if(!array_key_exists("id",$curr_row)){
				throw new Exception("Missing id");
			}
			$serial_obj->setId($curr_row['id']);
			$serial_obj = $this->register($serial_obj);
			
			$serial_obj = $serial_obj->build($curr_row);
			
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
	
	private function manyRelationSave(MySerializable $obj){
		if($obj->hasManyRelation()){
			$definition = $obj->definition();
			foreach($definition as $attr => $value){
				if(is_array($value)){
					$newRelationArray = $this->objectToIdList($obj->get($attr));
					$oldRelationArray = $this->objectToIdList($obj->getOriginalMultiRelations($attr));
					$deletedRelationArray = $this->relationArrayDiff($oldRelationArray, $newRelationArray);
					foreach($deletedRelationArray as $item){
						$relation = new Relation();
						$relation->setRelationName($attr);
						$relation->set("owner",$obj->id());
						if(gettype($item) == "integer"){
							$relation->set("property",$item);
						}else if(is_subclass_of($item,MySerializable::class)){
							$relation->set("property", $item->id());
						}else{
							throw new Exception('Not implemented');
						}
						$this->deleteRelation($relation);
					}
					foreach ($newRelationArray as $item){
						if(!$this->relationInArray($item,$oldRelationArray)){
							$relation = new Relation();
							$relation->setRelationName($attr);
							$relation->set("owner",$obj->id());
							if(gettype($item) == "integer"){
								$relation->set("property",$item);
							}else if(is_subclass_of($item,MySerializable::class)){
								$relation->set("property", $item->id());
							}else{
								throw new Exception('Not implemented');
							}
							$this->insertRelation($relation);
						}					
					}
				}
			}			
		}else{
			return true;
		}
	}
	
	private function compareRelation(Relation $rel1,Relation $rel2){
		return $rel1->get("owner") === $rel2->get("owner") && $rel1->get("property") === $rel2->get("property");
	}
	
	private function relationInArray($value, $array){
		$arr = $this->objectToIdList($array);
		foreach ($arr as $item){
			if(is_subclass_of($value,MySerializable::class)){
				if($item == $value->id()){
					return true;
				}
			}else if(gettype($value) === "integer"){
				if($item == $value){
					return true;
				}
			}
		}
		return false;
	}
	
	private function objectToIdList($array){
		$ret = array();
		foreach ($array as $value){
			if(is_subclass_of($value,MySerializable::class)){
				array_push($ret, $value->id());
			}else if(gettype($value) === "integer"){
				array_push($ret, $value);
			}
		}
		return $ret;
	}
	
	private function relationArrayDiff($array1, $array2){
		$ret = array();
		foreach ($array1 as $item1){
			$match = false;
			foreach ($array2 as $item2){
				if($item1 === $item2){
					$match = true;
				}
			}
			if(!$match){
				array_push($ret, $item1);
			}
		}
		return $ret;
	}
	
	private function insertRelation(Relation $rel){
		$conn = $this->connect();
		$stmt = $conn->prepare("INSERT INTO ".$rel->getRelationName()." (owner, property) VALUES (?, ?)");
		if(!$stmt){
			$conn->close();
			return false;
		}
		$stmt->bind_param("ii", $rel->get("owner"),$rel->get("property"));		
		if(!$stmt->execute()){
			$stmt->close();
			$conn->close();
			return false;
		}		
		$stmt->close();
		$conn->close();	
		return true;
	}
	
	private function deleteRelation(Relation $rel){
		$conn = $this->connect();
		$stmt = $conn->prepare("DELETE FROM ".$rel->getRelationName()." WHERE owner = ? AND property = ?");
		if(!$stmt){
			$conn->close();
			return false;
		}
		$stmt->bind_param("ii", $rel->get("owner"),$rel->get("property"));
		if(!$stmt->execute()){
			$stmt->close();
			$conn->close();
			return false;
		}
		$stmt->close();
		$conn->close();
		return true;
	}
	
	public function loadRelations($id,$attr,$classtype){
		
		$ret = array();
		$conn = $this->connect();
		$stmt = $conn->prepare("SELECT property FROM ".$attr." WHERE owner = ?");
		if(!$stmt){
			$conn->close();
			return null;
		}
		$properties = array();
		$stmt->bind_param("i", $id);
		if($stmt->execute()){
			$result = $stmt->get_result();
			while ($row = $result->fetch_assoc()) {
				array_push($properties, $row['property']);
			}			
			$stmt->free_result();
			$stmt->close();	
			$conn->close();
			
			foreach($properties as $propertyid){
				if(ObjectRegistry::getInstance()->inRegistry($classtype, $propertyid)){
					array_push($ret, ObjectRegistry::getInstance()->getFromRegistry($classtype, $propertyid));
				}else{
					$filter = new SqlFilter();
					$filter->addand("id","=",$propertyid);
					$classname = $classtype;
					$res_obj = $classname::selectOne($filter);
					if($res_obj != null){
						array_push($ret,$res_obj);
					}else{
						$this->invalidDatafield();
					}
				}
			}									
			return $ret;
		}else{
			$stmt->close();
			$conn->close();
			return null;
		}		
	}
	

}



?>