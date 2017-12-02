<?php

interface IPersistenceApi{
	
	function insert(MySerializable $serializable);
	function update(MySerializable $serializable);
	function select($serializableclassname,SqlFilter $sqlfilter);
	function delete(MySerializable $serializable);
	static function getInstance();
	
}

class DataTypes{
	
	const STRING = 0;
	const DATE = 1;
	const INT = 2;
	const BOOL = 3;
	
	static function normal($type){
		$simple = array(
				DataTypes::STRING,
				DataTypes::DATE,
				DataTypes::BOOL,
				DataTypes::INT
		);
		if(in_array($type, $simple)){
			return true;
		}else{
			return false;
		}
	}
}

abstract class ArrayTypes{

	const STRING = array(DataTypes::STRING);
	const DATE = array(DataTypes::DATE);
	const INT = array(DataTypes::INT);
	const BOOL = array(DataTypes::BOOL);

}


?>