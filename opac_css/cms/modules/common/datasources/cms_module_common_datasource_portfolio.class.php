<?php
// +-------------------------------------------------+
// � 2002-2012 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: cms_module_common_datasource_portfolio.class.php,v 1.1.4.2 2014-11-18 17:21:38 arenou Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

class cms_module_common_datasource_portfolio extends cms_module_common_datasource_list{
	
	public function __construct($id=0){
		parent::__construct($id);
		$this->sortable = true;
		if(!$this->parameters['sort_by']){
			$this->parameters['sort_by'] = "document_create_date";
		}
		if(!$this->parameters['sort_order']){
			$this->parameters['sort_order'] = "desc";
		}
	}
	/*
	 * On d�fini les s�lecteurs utilisable pour cette source de donn�e
	 */
	public function get_available_selectors(){
		return array(
			"cms_module_common_selector_documents"
		);
	}
	
	
	protected function get_sort_criterias() {
		return array(
			"document_create_date",
			"document_filesize",
			"document_title",
			"document_filename",
			"document_mimetype"
		);
	}
	/*
	 * R�cup�ration des donn�es de la source...
	 */
	public function get_datas(){
		global $dbh;
		$documents = array();
		//on commence par r�cup�rer l'identifiant retourn� par le s�lecteur...
		$selector = $this->get_selected_selector();
		if($selector){
			$docs = $selector->get_value();
			
			
			$valid = $this->filter_datas($docs['type_object'], array($docs['num_object']));
			if(($docs['num_object'] == $valid[0]) && is_array($docs['ids'])){
				if($this->parameters['sort_by']){
					$query = "select id_document from cms_documents where id_document in (".implode(",",$docs['ids']).") order by ".$this->parameters['sort_by']." ".$this->parameters['sort_order'];
					$result = mysql_query($query,$dbh);
					if(mysql_num_rows($result)){
						$docs['ids'] = array();
						while($row = mysql_fetch_object($result)){
							$docs['ids'][] = $row->id_document;
						}
					}
				}
				foreach($docs['ids'] as $document_linked){
					$document = new cms_document($document_linked);
					$documents[] = $document->format_datas();
				}
			}
		}
		return array(
			'documents'=>$documents,
			'nb_documents' => count($documents),
			'type_object' => $docs['type_object'],
			'num_object' => $docs['num_object']
		);
	}
	
	public function get_format_data_structure(){
		return array(
			array(
				'var' => "documents",
				'desc' => $this->msg['cms_module_common_datasource_portfolio_documents'],
				'children' => $this->prefix_var_tree(cms_document::get_format_data_structure(),"documents[i]")
			),
			array(
				'var' => "nb_documents",
				'desc' => $this->msg['cms_module_common_datasource_portfolio_nb_documents']
			),
			array(
				'var' => "type_object",
				'desc' => $this->msg['cms_module_common_datasource_portfolio_type_object']
			),
			array(
				'var' => "num_object",
				'desc' => $this->msg['cms_module_common_datasource_portfolio_num_object']
			)
		);
	}		
}