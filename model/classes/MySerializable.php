<?php

abstract class MySerializable implements ISerializable{

	//Add new specific attributes in every child
	protected $attributes;
	
	//list of changed attributes
	protected $changed = array();
	
	protected $default_deleted = true;
		
	//return name of class
	abstract public static function name();
	
	public function __construct(){
		$definition = $this->definition();
		foreach($definition as $key => $value){
			switch (true){
				case $value === DataTypes::BOOL :{
					$this->attributes[$key] = false;
					break;
				}
				case $value === DataTypes::STRING :{					
					$this->attributes[$key] = "";
					break;					
				}
				case $value === DataTypes::INT :{
					$this->attributes[$key] = 0;
					break;
				}
				case $value === DataTypes::DATE :{
					$this->attributes[$key] = new DateTime();
					break;
				}	
				default:{
					if(is_subclass_of($value,MySerializable::class)){
						$this->attributes[$key] = null;
					}else{
						throw new Exception('Not implemented');
					}
					break;
				}
			}
		}
	}

	public function serialize(){
		$ret =  $this->attributes;
		$def = $this->definition();
		unset($ret["id"]);
		unset($ret["deleted"]);		
		foreach($ret as $key => $value){
			$type = $def[$key];
			switch (true){
				case $type === DataTypes::STRING:
					$ret[$key] = $value;
					break;
				case $type === DataTypes::INT:
					$ret[$key] = $value;
					break;
				case $type === DataTypes::BOOL:
					$ret[$key] = (int)$value;
					break;
				case $type === DataTypes::DATE:
					$ret[$key] = $value->format("Y-m-d H:i:s");
					break;
				default:
					if(is_subclass_of($def[$key],MySerializable::class)){
						if($value === null){
							$ret[$key] = 0;
						}else{
							$ret[$key] = $value->id();
						}					
						break;
					}else{
						throw new Exception('Not implemented');
					}
			}						
			
		}
		
		return $ret;
	}
	
	abstract public function definition();
	
	public function get($attr){
		if(array_key_exists($attr, $this->attributes)){
			return $this->attributes[$attr];
		}else{
			return null;
		}
		
	}

	public function set($attr,$value){
		if(array_key_exists($attr, $this->attributes)){
			$this->attributes[$attr] = $value;
			array_push($this->changed, $attr);
		}else{
			throw new Exception('No Attribute '.$attr. 'in Serializable');
		}
	}
	
	public function toJson(){
		$ret =  $this->attributes;		
		foreach($ret as $key => $value){
			if($value instanceof DateTime){
				$ret[$key] = $value->format("Y-m-d H:i:s");
			}
		}
		return json_encode($ret);
	}
	
	public function build($json_str){
		if(gettype($json_str) == "string"){
			$json = json_decode($json_str);
		}else{
			$json = $json_str;
		}
		
		//TODO *....1 *...* kapcsolat
		
		
		if(ObjectRegistry::getInstance()->inRegistry($this->dataType(), $json["id"])){
			return ObjectRegistry::getInstance()->getFromRegistry(get_called_class(), $json["id"]);
		}
		
		$types = $this->definition();
		foreach ($this->attributes as $key => $value){
			if(array_key_exists($key, $json)){
				switch(true){
					case $types[$key] === DataTypes::STRING:
						if(gettype($value) == "string"){
							$this->attributes[$key] = $json[$key];
						}else{
							$this->invalidDatafield();
						}
						break;
					case $types[$key] === DataTypes::BOOL:
						if($json[$key] === "0" || $json[$key] === false || $json[$key] === 0){
							$this->attributes[$key] = false;
						}else if($json[$key] === "1" || $json[$key] === true || $json[$key] === 1){
							$this->attributes[$key] = true;
						}else{
							$this->invalidDatafield();
						}
						break;
					case $types[$key] === DataTypes::INT:
						if(gettype($value) == "integer"){
							$this->attributes[$key] = $json[$key];
						}else{
							$this->invalidDatafield();
						}
						
						break;
					case $types[$key] === DataTypes::DATE:
						if($this->validateDateTime($json[$key])){
							$this->attributes[$key] = new DateTime($json[$key]);
						}else{
							$this->invalidDatafield();
						}
						break;
					default:
						if(is_subclass_of($types[$key],MySerializable::class)){
							if($value == null){
								//DO NOTHING object reference not set yet
							}else if(gettype($value) == "integer"){
								if(ObjectRegistry::getInstance()->inRegistry($value->dataType(), $value->id())){
									$this->attributes[$key] = ObjectRegistry::getInstance()->getFromRegistry($value->dataType(), $value->id());
								}else{
									$filter = new SqlFilter();
									$filter->addand("id","=",$value);
									$obj = static::selectOne($filter);
									if($obj != null){
										$this->attributes[$key] = $obj;
									}else{
										$this->invalidDatafield();
									}
								}
							}else if($this->dataType() == $value->dataType()){
								$this->attributes[$key] = $value;
							}else{
								$this->invalidDatafield();
							}
							break;
						}else{
							throw new Exception('Not implemented');
						}
						break;
				}				
			}		
		}
		return $this;
	}
	
	protected function invalidDatafield(){
		throw new Exception("Invalid Object Build");
	}
	
	public function id(){
		return $this->get("id");
	}
	
	public function setId($id){
		$this->set("id", $id);
	}
	
	public function deleted(){
		return $this->get("deleted");
	}
	
	public function delete(){
		$this->set("deleted", true);
		SqlApi::getInstance()->delete($this);
	}
	
	public function restore(){
		$this->set("deleted", false);
		SqlApi::getInstance()->restore($this);
	}
	
	public function save(){
		if($this->id() == 0){
			$this->insert();
		}else{
			$this->update();
		}	
	}
	
	public function insert(){
		if(SqlApi::getInstance()->insert($this)){
			$this->changed = array();
			return true;
		}else{
			return false;
		}
	}
	
	public function update(){
		if(SqlApi::getInstance()->update($this)){
			$this->changed = array();
			return true;
		}else{
			return false;
		}
	}
	
	public static function select($filter_obj){
		return SqlApi::getInstance()->select(static::name(), $filter_obj);
	}
	
	public static function selectOne($filter_obj){
		
		$ret = SqlApi::getInstance()->select(static::name(), $filter_obj);
		if(count($ret) > 0){
			return $ret[0];
		}else{
			return null;
		}		
	}
	
	public function changed(){
		return $this->changed;
	}
	
	public function default_deleted(){
		return $this->default_deleted;
	}
	
	private function validateDateTime($dateStr)
	{
		if($dateStr === "0000-00-00 00:00:00"){
			return DateTime::createFromFormat('Y-m-d H:i:s', "0000-00-00 00:00:00");
		}
		date_default_timezone_set('UTC');
		$date = DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
		return $date && ($date->format('Y-m-d H:i:s') === $dateStr);
	}
	
	public function dataType(){
		return get_called_class();
	}
	
	public function arrayType(){
		return " ".$this->dataType();
	}
	
}

?>