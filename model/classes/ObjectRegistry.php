<?php

class ObjectRegistry{
	
	private $objectsRegistry = array();	
	
	public function register(MySerializable $obj){
		if($this->inRegistry($obj->dataType(),$obj->id())){
			return $this->getFromRegistry($obj->dataType(),$obj->id());
		}
		if(array_key_exists($obj->dataType(), $this->objectsRegistry)){
			$this->objectsRegistry[$obj->dataType()][$obj->id()] = $obj;
		}else{
			$this->objectsRegistry[$obj->dataType()] = array($obj->id() => $obj);
		}
		return $obj;
	}
	
	public function inRegistry($class, $id){
		if(array_key_exists($class, $this->objectsRegistry)){
			if(array_key_exists($id,$this->objectsRegistry[$class])){
				return true;
			}
		}
		return false;
	}
	
	public function getFromRegistry($class, $id){
		if($this->inRegistry($class, $id)){
			return $this->objectsRegistry[$class][$id];
		}else{
			return null;
		}
	}
	
	public static function getInstance(){
		static $inst = null;
		if ($inst === null) {
			$inst = new ObjectRegistry();
		}
		return $inst;
	}
	
	public function log(){
		foreach($this->objectsRegistry as $classname => $list){
			foreach ($list as $id => $obj){
				echo $obj->toJson();
				echo "<br/>";
			}
		}
	}
}