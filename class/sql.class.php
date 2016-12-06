<?php

class sql{

	var $query_ 	= '';
	var $select_ 	= array();
	var $from_ 		= array();
	var $join_ 		= array();
	var $where_		= array();
	var $order_		= '';
	var $group_		= '';
	var $having_	= '';
	var $limit_ 	= array(0,100);
	var $tab_default = '';

	function select( $tab, $columns = '*' ){

		if( $columns ){
			$this->select_[$tab][] = $columns;	
		} else {
			if( !array_key_exists( $tab ,  $this->select_ ) ){
				$this->select_[$tab] = '';
			}
		}
		
		return $this;
	}

	function from( $tab, $alias = NULL ){

		if( !$alias ) $alias = $tab;

		$this->from_[$tab] = $tab;
		
		return $this;
	}

	function where( $tab, $field, $value ){

		$this->where_[$tab] = array( 'column' 	=> $field,
									 'value'	=> $value );
		
		return $this;
	}

	function join( $tab , $column = NULL, $join = 'JOIN', $tab1 = NULL, $column2 = NULL ){

		if( !$tab1 ) $tab1 = $this->tab_default;
		if( !$column2 ) $column2 = $column;

		$this->join_[$tab] = array( $join, $tab, $column, $column2, $tab1 );
		return $this;

	}

	function limit( $limit = 100, $page = 1 ,$from = 0 ){

		$from = ( $page - 1 ) * $limit;
		$this->limit_ = array( $from, $limit );
		return $this;

	}

	function _do(){

		// SELECT 
		$sel = '';
		foreach( $this->select_ as $index => $value ){
			foreach( $value as $v){
				$sel[] = $index.".".$v;
			}			
		}		
		$select = "SELECT ".implode(",", $sel);

		foreach( $this->from_ as $i => $f){
			$from = " FROM ". $f;
			if( $i != $f ) $from .= " ".$i;
			break;
		}

		$joins_str = "";		
		if( count( $this->join_ ) ){
			foreach( $this->join_ as $t => $j){
				$joins[] = " ".$j[0]." ".$j[1]." ON ".$t.".".$j[2]." = ".$j[4].".".$j[3];
			}	
			$joins_str = implode('', $joins);
		}

		$where_str = "";
		if( count( $this->where_ ) ){
			foreach( $this->where_ as $t => $w){
				$where[] = " ".$t.".".$w['column']." = '".$w['value']."' ";
			}	
			$where_str = " WHERE ".implode(' AND ', $where);
		}


		
		$this->query_ = $select."  ".$from."  ".$joins_str." ".$where_str." LIMIT ".implode(',',$this->limit_);


		return $this;

	}

	function _print(){
		//echo $this->query_;
		if ( preg_match( '/(SELECT.*)(FROM.*)(WHERE.*)*(ORDER\sBY.*)*(GROUP\sBY.*)*(HAVING.*)*(LIMIT.*)*/i', $this->query_, $match ) ){
				
			$output = "";

			$select = "";
			$select_ = $match[1];
			preg_match('/(SELECT\s+)(.*)/i', $select_, $mselect);
			$select = $mselect[1];
			$sfields = $mselect[2];


			$from_ = $match[2];
			preg_match('/(FROM\s+)(.*)/i', $from_, $mfrom);
			$from = $mfrom[1];
			$ffields = $mfrom[2];

			$limit = $match[7];


			$output .= str_pad($select, 10, " ") .$sfields."<BR>";
			$output .= str_pad($from, 10, " ") .$ffields ."<BR>";

			return $output;

		}


	}




}