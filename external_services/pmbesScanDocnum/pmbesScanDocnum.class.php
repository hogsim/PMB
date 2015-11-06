<?php
// +-------------------------------------------------+
// | 2002-2012 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: pmbesScanDocnum.class.php,v 1.1.2.1 2015-03-17 10:35:52 abacarisse Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

require_once($class_path."/external_services.class.php");
require_once($class_path."/explnum.class.php");

class pmbesScanDocnum extends external_services_api_class {
	var $error=false;		//Y-a-t-il eu une erreur
	var $error_message="";	//Message correspondant � l'erreur
	
	function restore_general_config() {
		
	}
	
	function form_general_config() {
		return false;
	}
	
	function save_general_config() {
		
	}
	
	function get_doc_num($explnum, $upload_folder) {
		global $pmb_set_time_limit, $dbh;		
		$report=array();
		
		if (SESSrights & ADMINISTRATION_AUTH) {
			@set_time_limit($pmb_set_time_limit);
			
			if(is_array($explnum) && $upload_folder) {
				
				$idLien='';
				if($explnum['explnum_bulletin']){
					$query="SELECT 1 FROM bulletins WHERE bulletin_id=".$explnum['explnum_bulletin'];
					$result=mysql_query($query,$dbh);
					
					if(mysql_num_rows($result)){
						$idLien='explnum_bulletin='.$explnum['explnum_bulletin'];
					}
				}elseif($explnum['explnum_notice']){
					$query="SELECT 1 FROM notices WHERE notice_id=".$explnum['explnum_notice'];
					$result=mysql_query($query,$dbh);
						
					if(mysql_num_rows($result)){
						$idLien='explnum_notice='.$explnum['explnum_notice'];
					}
				}

				if($idLien){
					if(file_exists($upload_folder.$explnum['explnum_nomfichier'])){
					
						if(!$explnum['explnum_nom']){
							$explnum['explnum_nom']=$explnum['explnum_nomfichier'];
						}
						
						if(!$explnum['explnum_repertoire']){
							$explnum['explnum_repertoire']='1';
						}
						
						if(!$explnum['explnum_statut']){
							$explnum['explnum_statut']='0';
						}
						
						if(!$explnum['explnum_path']){
							$explnum['explnum_path']='/';
						}
						
						if(!$explnum['explnum_extfichier']){
							$match=array();
							if(preg_match('/.+\.(.+)$/', $explnum['explnum_nomfichier'], $match)){
								$explnum['explnum_extfichier']=$match[1];
							}
						}
						
						if(!$explnum['explnum_mimetype']){
							$explnum['explnum_mimetype']=trouve_mimetype($upload_folder.$explnum['explnum_nomfichier'],$explnum['explnum_extfichier']);
						}
						
						if(!$explnum['explnum_vignette']){
							$explnum['explnum_vignette']=reduire_image($upload_folder.$explnum['explnum_nomfichier']);
						}
						
						$query='INSERT INTO explnum SET '.$idLien.',
								explnum_nom="'.addslashes($explnum['explnum_nom']).'",
								explnum_mimetype="'.addslashes($explnum['explnum_mimetype']).'",
								explnum_extfichier="'.addslashes($explnum['explnum_extfichier']).'",
								explnum_nomfichier="'.addslashes($explnum['explnum_nomfichier']).'",
								explnum_vignette="'.addslashes($explnum['explnum_vignette']).'",	
								explnum_path="'.addslashes($explnum['explnum_path']).'",
								explnum_repertoire="'.addslashes($explnum['explnum_repertoire']).'",
								explnum_statut="'.addslashes($explnum['explnum_statut']).'"';
						
						mysql_query($query,$dbh);
						$explnum['explnum_id']=mysql_insert_id($dbh);
						
						if($explnum['explnum_id']){
							
							//on r�cup le r�pertoire dans lequel envoyer le fichier
							$upload_repertoire=new upload_folder($explnum['explnum_repertoire']);
							
							if(!is_dir($upload_repertoire->decoder_chaine($upload_repertoire->repertoire_path))){
								$report['error'][]=$this->msg['get_doc_num_upload_repertoire_do_not_exist'];
								//On efface l'entr�e
								$query='DELETE FROM explnum WHERE explnum_id='.$explnum['explnum_id'];
								mysql_query($query,$dbh);
							
							}
							if(!rename($upload_folder.$explnum['explnum_nomfichier'],$upload_repertoire->decoder_chaine($upload_repertoire->repertoire_path).$explnum['explnum_nomfichier'])){
								$report['error'][]=$this->msg['get_doc_num_rename_error'];
								//On efface l'entr�e
								$query='DELETE FROM explnum WHERE explnum_id='.$explnum['explnum_id'];
								mysql_query($query,$dbh);
							}else{
								//R�ussi ici, on r�index et on incr�mente le r�sultat
								$obj_explnum=new explnum($explnum['explnum_id']);
								$index = new indexation_docnum($explnum['explnum_id']);
								$index->indexer();
								
								$report['info']=1;
							}
							
						}else{
							//erreur de d�placement ou de cr�ation
							$report['error'][]=$this->msg['get_doc_num_cant_create_explnum'];
						}
						
					}else{
						$report['error'][]=$this->msg['get_doc_num_file_not_found'];
						//On efface l'entr�e
						$query='DELETE FROM explnum WHERE explnum_id='.$explnum['explnum_id'];
						mysql_query($query,$dbh);
					}
				}else{
					//doc num sans id notice ou bulletin
					$report['error'][]=$this->msg['get_doc_num_missing_link'];
				}
			}else{
				//il manque un param
				$report['error'][]=$this->msg['get_doc_num_missing_param'];
			}
		}
		return $report;
	}
}
?>