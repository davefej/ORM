<?php 

function model_base_require(){
	

	require __DIR__.'/interfaces/IPersistenceApi.php';
	require __DIR__.'/interfaces/ISerializable.php';
	require __DIR__.'/classes/MySerializable.php';
	require __DIR__.'/classes/SqlApi.php';
	require_model('User.php');

}

function require_model($modelname){
	require_once __DIR__.'/'.$modelname;
}
?>