<?php

abstract class MySerializable implements ISerializable{

	//Add new specific attributes in every child
	protected $attributes;
	
	//list of changed attributes
	protected $changed = array();
	
	protected $default_deleted = true;
		
	//return name of class
	public abstract static function name();

	public function seriaize(){
		$ret =  $this->attributes;
		unset($ret["id"]);
		unset($ret["deleted"]);
		
		foreach($ret as $key => $value){
			if($value instanceof DateTime){
				$ret[$key] = $value->format("Y-m-d H:i:s");
			}else if(gettype($value) == "boolean"){
				$ret[$key] = (int)$value;
			}
			
			
		}
		
		return $ret;
	}
	
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
		foreach ($this->attributes as $key => $value){
			if(array_key_exists($key, $json)){
				if($key == "deleted"){
					if($json[$key] === "0" || $json[$key] === false || $json[$key] === 0){
						$this->attributes[$key] = false;
					}else{
						$this->attributes[$key] = true;
					}
				}else{
					if($this->validateDateTime($json[$key])){
						$this->attributes[$key] = new DateTime($json[$key]);
					}else{
						$this->attributes[$key] = $json[$key];
					}
					
				}
				
			}		
		}
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
		date_default_timezone_set('UTC');
		$date = DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
		return $date && ($date->format('Y-m-d H:i:s') === $dateStr);
	}
	
}

?>