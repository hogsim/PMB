<?php
// +-------------------------------------------------+
// � 2002-2012 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: cms_module_common_datasource_records_bannette.class.php,v 1.1.2.3 2015-04-09 16:41:24 arenou Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");
require_once($include_path."/bannette_func.inc.php");

class cms_module_common_datasource_records_bannette extends cms_module_common_datasource_list{

	public function __construct($id=0){
		parent::__construct($id);
		$this->limitable = true;
	}
	/*
	 * On d�fini les s�lecteurs utilisable pour cette source de donn�e
	 */
	public function get_available_selectors(){
		return array(
			"cms_module_common_selector_bannette",
			"cms_module_common_selector_type_section",
			"cms_module_common_selector_type_article"
		);
	}

	/*
	 * R�cup�ration des donn�es de la source...
	 */
	public function get_datas(){
		//on commence par r�cup�rer l'identifiant retourn� par le s�lecteur...
		if($this->parameters['selector'] != ""){
			for($i=0 ; $i<count($this->selectors) ; $i++){
				if($this->selectors[$i]['name'] == $this->parameters['selector']){
					$selector = new $this->parameters['selector']($this->selectors[$i]['id']);
					break;
				}
			}
			$bannettes = $selector->get_value();
			if(is_array($bannettes) && count($bannettes)){
				foreach ($bannettes as $bannette_id){
					$records = $notices = array();
					notices_bannette($bannette_id, $notices);
					foreach($notices as $id => $niv){
						$records[]=$id;
					}
				}
			}
			$records = $this->filter_datas("notices",$records);
			if($this->parameters['nb_max_elements'] > 0){
				$records = array_slice($records, 0, $this->parameters['nb_max_elements']);
			}
			$return = array(
					'title'=> 'Liste de Notices',
					'records' => $records
			);
			
			return $return;
		}
		return false;
	}
}