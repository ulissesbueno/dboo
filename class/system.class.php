<?php
include 'setting.php';
include 'sql.class.php';
include 'normalize.class.php';
include 'connect.php';
include_once 'SqlFormatter.php';

spl_autoload_register(function ($class_name) {
    
	$sys = new system;
	if( $columns = $sys->FindTable($class_name) ){

		//echo "<pre>".print_r( $columns, 1 )."</pre>";
		$props = '';
		$fld_rule_sbmt = '';
		$pri = '';

		foreach( $columns as $v ){
			
			$fld_rule_sbmt[$v['Field']] = $v;
			$props[] = 'public static $'.$v['Field'].' = "'.$v['Default'].'" ;';
			if( $v['Key'] == 'PRI' && !$pri ) $pri = $v['Field'];
		}
		//echo "<pre>".print_r( $props, 1 )."</pre>";
		eval(' class  '.$class_name.' extends system { '.implode( '' , $props  ).' 
														var $fld_rule_sbmt = "'. addslashes( json_encode( $fld_rule_sbmt,1) ) .'";
														var $table = "'.$class_name.'";
														var $pri = "'.$pri.'";


													 }' );
	}

});

class system{

	var $rule_submit_filed = array();

	var $con = '';
	var $msg = '';
	var $success = true;
	var $result = '';
	var $datasql = array();

	// Connect mysql
	function __construct( $id = NULL ){

		$this->con = Database::getInstance();
		if( $id ) $this->setid( $id );
	}

	function setid( $value ){

		$select = " SELECT * FROM ".$this->table." WHERE ".$this->pri." = '".$value."'";
		$res = $this->con->query( $select );
		if( $res->num_rows){
			$this->success = true;

			$row = $res->fetch_assoc();
			foreach ($row as $key => $value) {
				$this->$key = $value;
				if( !$this->success ){
					echo $this->msg;
					break;
				}
			}

		} else {
			$this->success = false;
			$this->msg = 'Nenhum registro encontrado';
		}
		
		return $this;
	}

	function get( $index ){
		if( array_key_exists( $index , $this->datasql ) ){
			return $this->datasql[$index];	
		}
		
	}

	function showdata(){
		echo "<pre>".print_r( $this, 1 )."</pre>";
	}


	function FindTable( $table ){

		$select = "SHOW TABLES WHERE Tables_in_".BASE." LIKE '".$table."' ";
		
		$res = $this->con->query( $select);
		if( $res->num_rows ){
			
			return $this->ColumnsTable( $table );

		} else {
	
			return false;

		}
	}

	private function ColumnsTable( $table ){

		$select = "SHOW COLUMNS FROM ".$table." ";		
		$res = $this->con->query( $select);
		if( $res->num_rows ){
			
			$columns = '';
			while( $row = $res->fetch_assoc() ){
				$columns[] = $row;	
			}

			return $columns;

		} 

	}

	function addSetSQL( $name, $value ){
		$this->datasql[$name] = $value;
	}

	function save( $post = NULL ){
		
		if( $post ){
			// Valida tipos
			foreach ($post as $key => $value) {
				if( !$this->__set($key, $value) ){
					return false;
				}
			}
		} else {
			// verifica se os tipos estão validados
			if( !$this->success ) return false;
		}

		// pega regra
		$rules = $this->getRules();
		foreach ($rules as $key => $value) {
			
			// verifica se é obrigatório
			if( $value['Null'] == 'NO' && 
				!array_key_exists($key, $this->datasql) &&
				$value['Key'] != 'PRI'  ){
				$this->success = false;
				$this->msg = "O Campo '".$key."' é obrigatório!";
				break;
			}
		}

		if( !$this->success = $this->dosql() ){
			$this->msg = "Erro ao executar SQL: ".$this->query;
		}

		return $this->success;
		//echo "<pre>".print_r( $this->datasql, 1 )."</pre>";
		
	}

	var $query = "";

	function dosql(){

		// é update ?
		if( array_key_exists($this->pri, $this->datasql) && 
			isset( $this->datasql[$this->pri] ) ){

			$pre = "UPDATE ";
			$where = " WHERE ".$this->pri." = '".$this->datasql[$this->pri]."' ";

		} else {

			$pre = "INSERT INTO ";
			$where = '';

		}

		$sets = "";
		foreach( $this->datasql as $index => $value ){
			$sets[] = "  ".$index." = '".$value."' ";
		}

		$this->query = $pre.$this->table." SET ".implode( " , ",$sets ).$where;
		return $this->con->query($this->query );

	}

	var $selection = array();

	function select( $sel ){

		$sel = preg_replace('/\s+/','',$sel);

		if(preg_match_all('/(.[^\,]*?)(\,|$)/i', $sel, $matches)){
			foreach( $matches[1] as $s ){
				if( preg_match('/(.[^\.]*)\.*(.*)/i', $s, $match) ){
					if( $match[2] ){
						$this->selection[$match[1]][] = $match[2];
					} else {
						$this->selection[$this->table][] = $match[1];
					}
				}
			}
		}

		return $this;
	}

	var $normalize_ = array();
	var $normalize_class = '';

	function normalize( $column, $method, $alias = '' ){
		
		$this->normalize_class = new normalize;
		if( method_exists( $this->normalize_class , $method ) ){

			$this->normalize_[$column] = $method;

		} else {
			$this->success = false;
			$this->msg = "Método Noramalize não existe!";
		}

		return $this;

	}

	var $limit = 20;
	var $page = 1;

	function grid(){

		$sql = new sql;
		$data_sel = '*';

		if( count( $this->selection ) ){
			if( array_key_exists( $this->table , $this->selection) ) $data_sel = $this->selection[$this->table];
			$basic = $sql->select( $this->table, $data_sel )->from( $this->table );
		} else {
			$basic = $sql->select( $this->table, '*' )->from( $this->table );
		}		

		$select = "	SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME , CONSTRAINT_NAME 
					FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
					WHERE TABLE_NAME = '".$this->table."' AND CONSTRAINT_NAME != 'PRIMARY' AND REFERENCED_TABLE_NAME != '' ";
		$res = $this->con->query( $select );
		if( $res->num_rows ){
			while ($row = $res->fetch_assoc()) {
				
				$tb = $row['REFERENCED_TABLE_NAME'];

				if( count( $this->selection ) ){
					if( array_key_exists( $tb , $this->selection) ){
						$data_sel = $this->selection[$tb] ;
						$basic->select( $tb, $data_sel );		
					} 
				} else {
					$basic->select( $tb, '*' );
				}
				
				$basic->join( $tb, $row['COLUMN_NAME'], "JOIN", $this->table );

			}
		}	

		if( $this->get($this->pri) )	{
			$basic->where( $this->table, $this->pri, $this->get($this->pri) );
		}

		$basic->limit( 20 , $this->page );

		//echo "<pre>". $basic->_do()->query_."</pre>";
		$this->query = $basic->_do()->query_;		
		$result = $this->con->query( $this->query );
		$this->jsonfy($result);
		return $this;
	}

	function jsonfy( $res ){	

		$json = '';
		while( $row = $res->fetch_assoc() ){
			foreach( $row as $index => $value ){

				if( array_key_exists( $index , $this->normalize_) ){
					$value = call_user_method( $this->normalize_[$index] , $this->normalize_class, $value );
				}

				$row[$index] = utf8_decode( $value );
			}
			$json[] = $row;
		}	

		$this->result = $json;
		return $json;	

	}

	function output(){

		$return = array('success' => $this->success,
						'message' => $this->msg,
						'data'	  => $this->result	);

		echo json_encode( $return , 1 ) ;
	}

	function getRules( $name = '' ){
		$fld_rule_sbmt = json_decode( $this->fld_rule_sbmt, 1 );
		$rule = $fld_rule_sbmt;
		if( $name ) $rule = $fld_rule_sbmt[ $name ];

		return $rule;
	}

	public function __set($name, $value){

        // valida o tipo de entrada de dados
        $rule = $this->getRules( $name );
		if( !$this->CheckType( $rule['Type'], $value, $rule['Null'] ) ){
			$this->msg = "Entrada inválida de dados: ".$name." ( ".$rule['Type']." )  = ".$value;
			$this->success = false;
		} else {
			$this->success = true;
			$this->addSetSQL( $name, $value );
		}

		return $this->success;

    }

    private function CheckType( $StringType, $value, $null ){

    	if( $null == 'YES' && !$value ) return true;

    	if( preg_match( '/(\w+)(\((.*?)\))*/i' , $StringType, $match) ){

    		// original type
    		switch( $match[1] ){
    			case 'bigint':
    			case 'int':
    			case 'tyint':

    				if( !is_numeric($value) ) return false;

    				break;

    			case 'varchar':
    			case 'char':


    				if( !is_string($value) ) return false;

    				break;

    			default :

    				return true;

    				break;
    				
    		}

    		// size - numeric
    		if( is_numeric( $match[3] ) ){
    			if( strlen($value) > $match[3] ) return false;
    		}
    	}

    	return true;
    }


	
}

