<?php
class SqlFilter implements IFilter{

	private $filter_str = "";
	private $orderby = "";
	private $values = array();
	private $filtercommands = array();

	public function reset(){
		$this->filter_str = "";
		$this->values = array();
		$this->filtercommands = array();
	}

	public function addand($attribute,$operator,$value){
		if($this->filter_str != ""){
			$this->filter_str .= " AND ";
		}
		$this->filter_str .= $attribute.=" ".$operator." ?";
		array_push($this->values, $value);
		
		array_push($this->filtercommands, array(
				"cmd" => "and",
				"value" => $value,
				"operator" => $operator
		));
	}

	public function addor($attribute,$operator,$value){
		if($this->filter_str != ""){
			$this->filter_str .= " OR ";
		}
		$this->filter_str .= $attribute.=" ".$operator." ?";
		array_push($this->values, $value);
		
		array_push($this->filtercommands, array(
				"cmd" => "or",
				"value" => $value,
				"operator" => $operator
		));
	}

	public function generate(){
		if($this->filter_str == ""){
			return " 1=? ".$this->orderby;
		}else{
			return $this->filter_str.$this->orderby;
		}

	}

	public function values(){
		if($this->filter_str == ""){
			return array(1);
		}else{
			return $this->values;
		}

	}

	public function orderby($str){
		$this->orderby = " ORDER BY ".$str." ";
	}

	public function reverseorderby($str){
		$this->orderby = " ORDER BY ".$str." DESC ";
	}


}
?>