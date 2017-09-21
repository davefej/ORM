<?php
interface IFilter{



	public function reset();

	public function addand($attribute,$operator,$value);

	public function addor($attribute,$operator,$value);

	public function generate();

	public function values();

	public function orderby($str);
	
	public function reverseorderby($str);


}
?>