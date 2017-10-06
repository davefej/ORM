<?php

abstract class MySerializable implements ISerializable{

	//Add new specific attributes in every child
	protected $attributes;
	
	//list of changed attributes
	protected $changed = array();
	
	protected $default_deleted = true;
	
	protected $originalMultiRelations = array();
		
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
				case is_array($value):
					$tip = $value[0];
					if(is_subclass_of($tip,MySerializable::class)){
						$this->attributes[$key] = array();
					}else{
						throw new Exception('Not implemented');
					}					
					break;				
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
		$ret =  array();
		$def = $this->definition();
		
		foreach($def as $key => $type){
			$value = $this->attributes[$key];
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
				case is_array($type):
					continue;
					/*
					$retval = array();
					foreach ($value as $item){
						if(gettype($item) == "integer"){
							array_push($retval, $item);
						}else if(is_subclass_of($item,MySerializable::class)){
							array_push($retval, $item->id());
						}else{
							throw new Exception('Not implemented');
						}
					}
					*/
					break;
				default:
					if(is_subclass_of($type,MySerializable::class)){
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
		unset($ret["id"]);
		unset($ret["deleted"]);
		
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
			$def = $this->definition();
			$def[$attr];
		
			switch(true){
				case $def[$attr] === DataTypes::BOOL && gettype($value) === "boolean" :{
					$this->attributes[$attr] = $value;
					array_push($this->changed, $attr);
					break;
				}
				case $def[$attr] === DataTypes::STRING && gettype($value) === "string" :{
					$this->attributes[$attr] = $value;
					array_push($this->changed, $attr);
					break;
				}
				case $def[$attr] === DataTypes::INT && gettype($value) === "integer" :{
					$this->attributes[$attr] = $value;
					array_push($this->changed, $attr);
					break;
				}
				case $def[$attr] === DataTypes::DATE && (is_a($value,'DateTime')) :{
					$this->attributes[$attr] = $value;
					array_push($this->changed, $attr);
					break;
				}
				case is_array($def[$attr]):
					$tip = $value[0];
					if(is_subclass_of($tip,MySerializable::class)){
						$this->attributes[$attr] = $value;
						array_push($this->changed, $attr);
					}else{
						throw new Exception('Data Format Exception');
					}
					break;
				default:{
					if(is_subclass_of($def[$attr],MySerializable::class)){
						$this->attributes[$attr] = $value;
						array_push($this->changed, $attr);
					}else{
						throw new Exception('Data Format Exception');
					}
					break;
				}
			}
		}else{
			throw new Exception('No Attribute '.$attr. 'in Serializable');
		}
/*			
		if(array_key_exists($attr, $this->attributes)){
			$this->attributes[$attr] = $value;
			array_push($this->changed, $attr);
		}else{
			throw new Exception('No Attribute '.$attr. 'in Serializable');
		}
*/
	}
	
	public function add($attr,$value){
		$def = $this->definition();
		if(is_array($def[$attr])){			
			if($this->attributes[$attr] === null){
				$this->attributes[$attr] = array($value);
			}else{
				array_push($this->attributes[$attr], $value);
			}
			return true;
		}else{
			return false;
		}
	}
	
	public function removeAll($attr,$value){
		$def = $this->definition();
		if(is_array($def[$attr])){		
			$this->attributes[$attr] = array();			
			return true;
		}else{
			return false;
		}
	}
	
	public function remove($attr,$value){
		
		if(gettype($value) == "integer"){
			$id = $value;
		}else if($value->dataType() == $this->dataType()){
			$id = $value->id();
		}else{
			return false;
		}
		$def = $this->definition();
		if(is_array($def[$attr])){			
			if($this->attributes[$attr] == null){
				return false;
			}	
			foreach($this->attributes[$attr] as $pos => $item){
				if(gettype($item) == "integer"){
					if($id === $item){
						unset($this->attributes[$attr],$pos);
						break;
					}
				}else if($item->dataType() == $this->dataType()){
					if($id === $item->id()){
						unset($this->attributes[$attr],$pos);
						break;
					}
				}
			}			
			return true;
		}else{
			return false;
		}
	}
	
	public function toJson(){
		$ret =  array();
		$def = $this->definition();
		foreach($def as $key => $type){
			$value = $this->attributes[$key];
			if($value == null){
				$ret[$key] = $value;
			}else if( $type === DataTypes::DATE){
				$ret[$key] = $value->format("Y-m-d H:i:s");
			}else if(is_subclass_of($type,MySerializable::class)){
				$ret[$key] = array("id" => $value->id());
			}else if(is_array($type)){
				$idlist = array();
				foreach($value as $item){					
					if(gettype($item) == "integer"){
						array_push($idlist, array("id" => $item));
					}else if($item->dataType() == $this->dataType()){
						array_push($idlist, array("id" => $item->id()));
					}
				}
				$ret[$key] = $idlist;
			}else{
				$ret[$key] = $value;
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
		
		if(ObjectRegistry::getInstance()->inRegistry($this->dataType(), $json["id"])){
			return ObjectRegistry::getInstance()->getFromRegistry(get_called_class(), $json["id"]);
		}
		
		$types = $this->definition();
		foreach ($this->definition() as $key => $value){		
			if(array_key_exists($key, $json)){
				$value = $json[$key];
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
					case is_array($types[$key]):
						$type = $types[$key][0];
						if(is_subclass_of($type,MySerializable::class)){
							if($value == null){
								$this->attributes[$key] = $this->loadManyRelations($json["id"],$key,$type);
								$this->setOriginalMultiRelations($key,$this->attributes[$key]);
							}else if(is_array($value)){
								$arr = array();
								foreach ($value as $item) {
									if(gettype($item) == "integer"){
										//Object id not object
										if(ObjectRegistry::getInstance()->inRegistry($type, $item)){
											array_push($arr,ObjectRegistry::getInstance()->getFromRegistry($type, $item));
										}else{
											$filter = new SqlFilter();
											$filter->addand("id","=",$item);
											$classname = $type;
											$res_obj = $classname::selectOne($filter);											
											if($res_obj != null){
												array_push($arr,$res_obj);
											}else{
												$this->invalidDatafield();
											}
										}
									}else if($item->dataType() == $this->dataType()){
										array_push($arr, $item);
									}									
								}
								$this->attributes[$key] = $arr;
								$this->setOriginalMultiRelations($key,$arr);
							}else{
								$this->invalidDatafield();
							}
						}else{
							$this->invalidDatafield();
						}						
						break;
					default:
						if(is_subclass_of($types[$key],MySerializable::class)){
							if($value == null){
								//DO NOTHING object reference not set yet
							}else if(gettype($value) == "integer"){
								//Object id not object
								if(ObjectRegistry::getInstance()->inRegistry($types[$key], $value)){
									$this->attributes[$key] = ObjectRegistry::getInstance()->getFromRegistry($types[$key], $value);
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
			}else{				
				if(is_array($types[$key])){//manyRelations
					$type = $types[$key][0];
					if(is_subclass_of($type,MySerializable::class)){
						$this->attributes[$key] = $this->loadManyRelations($json["id"],$key,$type);
						$this->setOriginalMultiRelations($key,$this->attributes[$key]);
					}
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
	
	public function loadManyRelations($id,$attr,$type){	
		return SqlApi::getInstance()->loadRelations($id,$attr,$type);		
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
			return true;//DateTime::createFromFormat('Y-m-d H:i:s', "0000-00-00 00:00:00");
		}
		date_default_timezone_set('UTC');
		$date = DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
		return $date && ($date->format('Y-m-d H:i:s') === $dateStr);
	}
	
	public function dataType(){
		return get_called_class();
	}
	
	public function arrayType(){
		return array($this->dataType());
	}
	
	public function hasManyRelation(){
		$definition = $this->definition();
		foreach($definition as $key => $value){
			if(is_array($value)){
				return true;
			}
		}
		return false;
	}
	
	public function setOriginalMultiRelations($attr,$value){
		$this->originalMultiRelations[$attr] = $value;
	}
	
	public function getOriginalMultiRelations($attr){
		if(array_key_exists($attr,$this->originalMultiRelations)){
			return $this->originalMultiRelations[$attr];
		}else{
			return array();
		}
		
	}
	
}

?>