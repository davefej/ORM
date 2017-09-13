<?php

interface IPersistenceApi{
	
	function insert($serializable);
	function update($serializable);
	function select($serializableclass,$filter);
	function delete($serializable);
	static function getInstance();
}

?>