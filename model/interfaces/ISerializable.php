<?php
/*
 * Serializable interface
 * Has two obligatory attribute id and deleted
 * serialize() must return and array of object-keys with the object parameters
 *  in that order, you want ot serialize it.
 *  Id and deleted parameters should not be in the returned array of serialize().
 */
interface ISerializable{

	public static function name();

	public function seriaize();

	public function get($attr);

	public function set($attr,$value);

	public function toJson();

	public function build($data);

	public function id();

	public function setId($id);

	public function deleted();

	public function delete();
	
	public function restore();

	public function save();

	public function update();
	
	public function insert();

	public static function select($filter_obj);
	
	public static function selectOne($filter_obj);
	
	public function changed();
	
	public function default_deleted();

}

?>