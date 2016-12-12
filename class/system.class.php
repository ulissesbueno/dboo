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
	var $filter = array();

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
				$this->result[ $key ] = utf8_encode($value);
				/*if( !$this->success ){
					break;
				}*/
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

	function addFilter( $name, $value ){
		if( $value ) $this->filter[$name] = $value;
	}

	function save( $post = NULL ){
		
		if( $post ){
			// Valida tipos
			foreach ($post as $key => $value) {
				if( !$this->__set($key, $value) ){
					return false;
				}
			}
		} 

		if( !$this->success ) return false;

		// pega regra
		$rules = $this->getRules();
		foreach ($rules as $key => $value) {
			
			// verifica se é obrigatório
			if( $value['Null'] == 'NO' && 
				!array_key_exists($key, $this->datasql) &&
				$value['Key'] != 'PRI'  ){
				$this->success = false;
				$this->msg = "O Campo '".$key."' é obrigatório!";
				return false;
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
			$sets[] = "  ".$index." = '". utf8_decode( $value )."' ";
		}

		$this->query = $pre.$this->table." SET ".implode( " , ",$sets ).$where;
		return $this->con->query($this->query );

	}

	var $selection = array();

	function putSelect( $sel ){

		$sel = preg_replace('/\s+/','',$sel);

		if(preg_match_all('/(.[^\,]*?)(\,|$)/i', $sel, $matches)){
			foreach( $matches[1] as $s ){
				if( preg_match('/(.[^\.]*)\.*(.*)/i', $s, $match) ){
					if( $match[2] ){
						$sel = $match[2];
						$this->selection[$match[1]][] = $match[2];
					} else {
						$this->selection[$this->table][] = $match[1];
					}
				}
			}
		}

		return $sel;

	}

	var $normalize_ = array();
	var $normalize_class = '';

	function select( $column, $label, $alias = '', $method = '', $show = true, $width = 100 ){
			
		$column = $this->putSelect( $column );

		if( $method ){
			$this->normalize_class = new normalize;
			if( !method_exists( $this->normalize_class , $method ) ){
				$this->success = false;
				$this->msg = "Método Noramalize não existe!";
			}	
		}

		$this->normalize_[$column] = array(	'label' 	=> $label,
											'alias' 	=> $alias,
											'method'	=> $method,
											'show'		=> $show,
											'width'		=> $width);
		
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

		if( $this->filter ){
			foreach ($this->filter as $key => $value) {
				$basic->where( $this->table, $key, $value );
			}
		}

		$basic->limit( 20 , $this->page );

		//echo "<pre>". $basic->_do()->query_."</pre>";
		$this->query = $basic->_do()->query_;		
		$result = $this->con->query( $this->query );
		$this->jsonfy($result);
		return $this;
	}

	var $columns_grid;

	function jsonfy( $res ){	
		//print_r( $this->normalize_);
		//echo "<br>";
		//echo "<pre>". $this->query."</pre>";
		$json = '';
		$columns = '';
		$first = true;
		while( $row = $res->fetch_assoc() ){
			foreach( $row as $index => $value ){
				
				$label = $index;
				$width = 100;

				if( array_key_exists( $index , $this->normalize_) ){

					if( $this->normalize_[$index]['method'] ){
						$value = call_user_method( $this->normalize_[$index]['method'] , $this->normalize_class, $value );	
					}						

					if( $this->normalize_[$index]['label'] ){
						$label = $this->normalize_[$index]['label'];
					}

					if( !$this->normalize_[$index]['show'] ){
						continue;
					}

					$width = $this->normalize_[$index]['width'];
				} else {
					//
				}

				if( $first ){					
					$this->columns_grid[] = array('field'=>$index, 'title' => $label, 'width' => $width );	
				}

				$row[$index] = utf8_encode( $value );
			}
			$first = false;
			$json[] = $row;
		}	

		$this->result = $json;
		return $json;	

	}

	function output(){

		$return = array('success' => $this->success,
						'message' => $this->msg,
						'data'	  => $this->result,
						'columns' => $this->columns_grid,
						'sql'	  => SqlFormatter::format($this->query));

		echo json_encode( $return , 1 ) ;
	}

	function getRules( $name = '' ){
		$fld_rule_sbmt = json_decode( $this->fld_rule_sbmt, 1 );
		$rule = $fld_rule_sbmt;
		if( $name ) $rule = $fld_rule_sbmt[ $name ];

		return $rule;
	}

	public function __set($name, $value){

		if( $name == $this->pri ) return true;

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

    private function getTypeSize( $StringType ){
    	if( preg_match( '/(\w+)(\((.*?)\))*/i' , $StringType, $match) ){
    		$type = $match[1];
    		$size = '';
    		if( isset($match[3]) ) $size = $match[3];
    		return (object) array('type' => $type, 'size' => $size);
    	}
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

    function hide(){
    	$last = end(array_keys($this->inputs_));
    	$this->inputs_[$last]['hide'] = 'yes';
    	return $this;
    }

    function switchButton( $values ){
    	$last = end(array_keys($this->inputs_));
    	$this->inputs_[$last]['switch'] = $values;
    	return $this;
    }


    var $inputs_ = '';
    function input( $field, $label = '' ){
    	$this->inputs_[$field]['Label'] = $label;
    	return $this;
    }

    var $formTitle_ = '';

    function formTitle( $text ){
    	$this->formTitle_  = $text;
    	return $this;
    }

    function form( $text = '' ){

    	$inputs = $this->getRules();
    	$inp = array();
    	$opts = '';
    	foreach ($inputs as $key => $value) {
    		$opts = '';
    		if( array_key_exists($key, $this->inputs_) ){

    			$class_easyui = 'textbox';
    			$maxsize = '';
    			$size = '50';

    			$typesize = $this->getTypeSize( $value['Type'] );

    			// regras Base de Dados por type
    			switch ( $typesize->type ) {
    				case 'text':
    					$size = '50';
    					$opts['multiline'] = 'true';

    					break;

    				case 'float':

    					$class_easyui = 'numberbox';

    					$opts['min'] = '0';
    					$opts['precision'] = '2';

    				case 'int':

    					$class_easyui = 'numberbox';
    					$opts['min'] = '0';
    				
    				default:
    					# code...
    					break;
    			}

    			if( $value['Null'] == 'NO' ){
    				$opts['required'] = 'true';
    			}

    			if( $this->inputs_[$key]['Label'] ){
    				//$opts['label'] = $this->inputs_[$key]['Label'];
    			}

    			
    			if( is_numeric($typesize->size) ){
    				$maxsize = $typesize->size;
    			}

    			if( array_key_exists('switch', $this->inputs_[$key])) {
    				$opts['onText'] = 'Sim';
    				$opts['offText'] = 'Não';
    				$opts['checked'] = 'true';
    				$opts['value'] = $this->inputs_[$key]['switch'];

    				$class_easyui = 'switchbutton';
    			}

    			$inputs[$key]['Maxsize'] = $maxsize;
    			$inputs[$key]['Size'] = $size;
    			$inputs[$key]['class'] = $class_easyui;

    			$inp[$key] = array_merge($inputs[$key], $this->inputs_[$key] );	
    			$inp[$key]['options'] = $opts;

    		}
    		
    	}

    	$this->formTitle_  = $text;
    	$this->result = array( 	'title' => $this->formTitle_,
    							'inputs' => $inp );
    	return $this;
    }

	
}

