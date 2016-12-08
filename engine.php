<?php

include 'class/system.class.php';

$uri = $_SERVER['REQUEST_URI'];
$partes = array_values(array_filter( explode('/',$uri) ));

// se estiver no padrão
if(preg_match( '/.+\/.+/i', $uri )){
	
	$class = $partes[0];
	$method = $partes[1];
	$args = array();
	// tem parametros
	if( count( $partes ) > 2 ){
		
		$args = array_slice( $partes , 2 );	

	}
	
	// nomeia arquivo
	$file_class = $class.'.class.php';
	// Existe o arquivo ?
	if( file_exists( 'controller/'.$file_class ) ){

		// include do arquivo da classe
		include_once( 'controller/'.$file_class ) ;

		if( class_exists( $class ) ){
			// instancia a classe
			$obj = new $class;

			// se houver argumentos e se for par
			if( count($args) && 
				count($args) % 2 == 0 ){
				
				for( $i = 0; $i < count($args); $i+= 2 ){

					if( property_exists( $class, $args[$i] ) ){
						$obj->$args[$i] = $args[$i+1];	
					} else {
						echo "Argumento não existe : ".$args[$i]." ";
						break;
						exit;
					}					

				}

			} 

			if( method_exists ( $obj , $method ) ){
				
				call_user_method( $method , $obj );

			} else {

				echo "Método não existe!";

			}
		} else {

			echo "Classe não existe";

		}
		

	} else {

		echo "Arquivo da classe não existe!";	

	}

} else {
	echo "Link fora do padrão!";
}

