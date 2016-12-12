<?php 

class botfacil{

	var $id = NULL;
	var $page = 1;
	var $category = NULL;
	
	function get_products(){
		
		$table = new produtos();
		$table->addFilter( 'CategoriaID', $this->category);

		if( $table->success ){

			$table->page = $this->page;

			$table	->select( 'ProdutoID', 'ID', NULL, NULL, true, '5%')
					->select( 'CategoriaID', 'CategoriaID',NULL,NULL, false )
					->select( 'SubcategoriaID', 'SubcategoriaID',NULL,NULL, false)
					->select( 'ProdutoNome', 'Nome', NULL, NULL, true, '30%')
					->select( 'ProdutoValor', 'Valor' , 'Valor' , 'money_br', true, '10%')
					->select( 'categorias.CategoriaNome', 'Categoria', NULL, NULL, true, '20%')
					->select( 'ProdutoFoto', 'Foto', 'Foto', NULL, false)
					->select( 'ProdutoDescricao', 'Descrição', 'ProdutoDescricao', NULL, true, '35%')
					->grid()->output();

		} else {

			$table->output();	
		}

		//echo SqlFormatter::format($table->query);		
	}

	function get_data_product(){
		$table = new produtos( $this->id );
		if( $table->success ){
			$table->output();	
		}
	}

	function frm_product(){
		
		$table = new produtos();
		if( $table->success ){
			$table->input('ProdutoID')->hide()
				  ->input('CategoriaID')->hide()
				  ->input('SubcategoriaID')->hide()
				  ->input('ProdutoNome','Nome')
				  ->input('ProdutoValor','Valor')
				  ->input('ProdutoDescricao','Descrição')
				  ->input('ProdutoEstoque','Tem Estoque')->switchButton( 1 )
				  ->input('ProdutoQtdeEstoque','Estoque Atual')
				  ->input('ProdutoEstoqueMin','Estoque Mínimo')
				  ->form('Cadastro Produto')
				  ->output();
		} else {
			$table->output();	
		}

		//echo SqlFormatter::format($table->query);		
	}

	function get_category(){
		
		$table = new categorias( $this->id );

		if( $table->success ){

			$table->page = $this->page;

			$table	->select( 'CategoriaID', 'ID')
					->select( 'CategoriaNome', 'Categoria')
					->grid()->output();	

		} else {

			$table->output();	
		}

		//echo SqlFormatter::format($table->query);
		
	}

	function get_company(){
		
		$table = new empresas( $this->id );

		if( $table->success ){

			$table->page = $this->page;

			$table	->select( 'EmpresaID', 'ID')
					->select( 'EmpresaCPFCNPJ', 'CNPJ')
					->select( 'EmpresaEmail', 'E-mail')
					->select( 'EmpresaNomeFantasia', 'Nome Fantasia')
					->grid()->output();	

		} else {

			$table->output();	
		}

		//echo SqlFormatter::format($table->query);
		
	}

	function get_order(){
		
		$table = new pedidos( $this->id );

		if( $table->success ){

			$table->page = $this->page;

			$table->select( '	PedidoID,
								PedidoData,
								PedidoTotal'
							  )
					->normalize( 'PedidoData', 'date_format', 'Data'  )
					->normalize( 'PedidoTotal', 'money_br', 'PedidoTotal' )
					->grid()->output();	

		} else {

			$table->output();	
		}

		//echo SqlFormatter::format($table->query);
		
	}

	var $frm;
	function send(){
		switch ( $this->frm ) {
			case 'product':
				
				$table = new produtos();
				$table->PerguntaID = 3;
				$table->ProdutoStatus = 1;
				$table->ProdutoEstoque = 0;
				$table->save($_POST);

				break;
			
			default:
				# code...
				break;
		}

		$table->output();	

	}

}
