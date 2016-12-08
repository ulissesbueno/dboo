<?php 

class botfacil{

	var $id = NULL;
	var $page = 1;
	
	function get_products(){
		
		$table = new produtos( $this->id );

		if( $table->success ){

			$table->page = $this->page;

			$table->select( '	ProdutoID,
							 	CategoriaID,
							 	SubcategoriaID,
							 	ProdutoNome,
							 	ProdutoValor,
							 	categorias.CategoriaNome'
							  )
					->normalize( 'ProdutoValor', 'money_br', 'Valor' )
					->grid()->output();	

		} else {

			$table->output();	
		}

		echo SqlFormatter::format($table->query);
		
	}

	function get_order(){
		
		$table = new pedidos( $this->id );

		if( $table->success ){

			$table->page = $this->page;

			$table->select( '	PedidoID,
								PedidoData,
								PedidoTotal'
							  )
					->normalize( 'PedidoData', 'date_format', 'Data' )
					->normalize( 'PedidoTotal', 'money_br', 'PedidoTotal' )
					->grid()->output();	

		} else {

			$table->output();	
		}

		echo SqlFormatter::format($table->query);
		
	}

}
