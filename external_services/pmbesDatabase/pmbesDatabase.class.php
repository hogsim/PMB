<?php
// +-------------------------------------------------+
// | 2002-2007 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: pmbesDatabase.class.php,v 1.4.2.1 2015-06-19 07:16:58 mbertin Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

require_once($class_path."/external_services.class.php");

class pmbesDatabase extends external_services_api_class {
	var $error=false;		//Y-a-t-il eu une erreur
	var $error_message="";	//Message correspondant � l'erreur
	var $es;				//Classe m�re qui impl�mente celle-ci !
	var $msg;
	
	function restore_general_config() {
		
	}
	
	function form_general_config() {
		return false;
	}
	
	function save_general_config() {
		
	}
	
	
	function get_current_version(){
		global $dbh;
		$query ="select valeur_param from parametres where type_param = 'pmb' and sstype_param ='bdd_version'";
		$result = mysql_query($query,$dbh);
		$pmb_bdd_version = "v1.0";
		if(mysql_num_rows($result)){
			$pmb_bdd_version = mysql_result($result,0,0);
		}
		return $pmb_bdd_version;
	}
	
	function get_current_subversion(){
		global $dbh;
		$query ="select valeur_param from parametres where type_param = 'pmb' and sstype_param ='bdd_subversion'";
		$result = mysql_query($query,$dbh);
		$pmb_bdd_subversion = "0";
		if(mysql_num_rows($result)){
			$pmb_bdd_subversion = mysql_result($result,0,0);
		}
		return $pmb_bdd_subversion;
	}
	
	function get_version_informations() {
		global $dbh;
		global $pmb_version_database_as_it_should_be;
		global $pmb_subversion_database_as_it_shouldbe;
		
		$tmp= array(
			'currentVersion' => $this->get_current_version(),
			'currentSubVersion' => $this->get_current_subversion(),
			'shouldbeVersion' => $pmb_version_database_as_it_should_be,
			'shouldbeSubVersion' => $pmb_subversion_database_as_it_shouldbe
		);
		return $tmp;
	}
	
	function need_update(){
		global $pmb_version_database_as_it_should_be;
		global $pmb_subversion_database_as_it_shouldbe;

		$result=array();
		$result['need'] = false;
		if($this->get_current_version()!=$pmb_version_database_as_it_should_be){
			$result['need'] = true;
		}elseif($this->get_current_subversion()!=$pmb_subversion_database_as_it_shouldbe){
			$result['need'] = true;
		}
		return $result;
	}
	
	function update(){
		global $base_path;
		global $dbh;
		global $lang;
		global $class_path;
		global $include_path;
		global $pmb_version_database_as_it_should_be;
		global $pmb_subversion_database_as_it_shouldbe;
		
		//Allons chercher les messages
		include_once("$class_path/XMLlist.class.php");
		$messages = new XMLlist("$include_path/messages/$lang.xml", 0);
		$messages->analyser();
		$msg = $messages->table;
		//les globales PMB ! 
		include($include_path."/start.inc.php");
		
		$result = array();
		$result['result'] = true;
		$result['informations'] = "";
		
		$check = $this->need_update();
		if($check['need']){
			ob_start();
			if($this->get_current_version()!=$pmb_version_database_as_it_should_be){
				$action = "lancement";
				$version_pmb_bdd = $pmb_bdd_version;
				switch (substr($pmb_bdd_version,0,2)) {
					case "v1":
						include ($base_path."/admin/misc/alter_v1.inc.php") ;
						break ;
					case "v2":
						include ($base_path."/admin/misc/alter_v2.inc.php") ;
						break ;
					case "v3":
						include ($base_path."/admin/misc/alter_v3.inc.php") ;
						break ;
					case "v4" :
						if(substr($pmb_version_database_as_it_should_be,0,2) == "v5" && ($pmb_bdd_version == "v4.97" || $pmb_bdd_version == "v4.96" || $pmb_bdd_version == "v4.95" || $pmb_bdd_version == "v4.94")){
							include ($base_path."/admin/misc/alter_v5.inc.php") ;
						}else{
							include ($base_path."/admin/misc/alter_v4.inc.php") ;
						}
						break ;
					case "v5" :
						include ($base_path."/admin/misc/alter_v5.inc.php") ;
						break ;
				}
				ob_get_contents();
				ob_end_clean();
				ob_start();
				$action = $maj_a_faire;
				switch (substr($pmb_bdd_version,0,2)) {
					case "v1":
						include ($base_path."/admin/misc/alter_v1.inc.php") ;
						break ;
					case "v2":
						include ($base_path."/admin/misc/alter_v2.inc.php") ;
						break ;
					case "v3":
						include ($base_path."/admin/misc/alter_v3.inc.php") ;
						break ;
					case "v4" :
						if(substr($pmb_version_database_as_it_should_be,0,2) == "v5" && ($pmb_bdd_version == "v4.97" || $pmb_bdd_version == "v4.96" || $pmb_bdd_version == "v4.95" || $pmb_bdd_version == "v4.94")){
							include ($base_path."/admin/misc/alter_v5.inc.php") ;
						}else{
							include ($base_path."/admin/misc/alter_v4.inc.php") ;
						}
						break ;
					case "v5" :
						include ($base_path."/admin/misc/alter_v5.inc.php") ;
						break ;
				}			
				$result['informations'] = ob_get_contents();
			}else{
				$addon=file_get_contents($base_path."/admin/misc/addon.inc.php");
				$addon=preg_replace("#function traite_rqt\(.*?AJOUTER ICI LES MODIFICATIONS#si","/******************** AJOUTER ICI LES MODIFICATIONS",$addon);
				file_put_contents($base_path."/temp/addon.inc.php", $addon);
				include($base_path."/temp/addon.inc.php");
				unlink($base_path."/temp/addon.inc.php");
			}
			ob_end_clean();
		}else {
			$result['informations'] = $this->msg['update_msg_database_already_updated'];
		}
		return $result;
	}
}