<?php

class normalize{

	function __construct(){
		
	}

	function money_br( $value, $row = NULL ){
		return "R$ ".$this->money( $value );
	}

	function money( $value, $row = NULL ){
		return number_format( $value, 2, ',' , '.' ) ;
	}

	function date_format( $value, $row = NULL ){
		
		//([0-9]+)\-([0-9]+)\-([0-9]+)\s*((.*)+) ---  $3/$2/$1 $4 --- Ex: 2016-01-10 00:00:00
		$value = preg_replace( '/([0-9]+)\-([0-9]+)\-([0-9]+)\s*((.*)+)/' , "$3/$2/$1 $4", $value);
		return $value ;
		
	}

	function img( $value, $row = NULL ){
		return "<img src='file/".$value."' />" ;
	}



}