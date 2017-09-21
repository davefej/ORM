<?php

interface IPersistenceApi{
	
	function insert(MySerializable $serializable);
	function update(MySerializable $serializable);
	function select($classname,$filter);
	function delete(MySerializable $serializable);
	static function getInstance();
	
}

abstract class DataTypes
{
	const STRING = 0;
	const DATE = 1;
	const INT = 2;
	const BOOL = 3;
	

}

?>