<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: audit.class.php,v 1.20 2015-05-20 14:49:27 dgoron Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

if ( ! defined( 'AUDIT_CLASS' ) ) {
  define( 'AUDIT_CLASS', 1 );

class audit {
	
	// ---------------------------------------------------------------
	//		propri�t�s de la classe
	/*
	CREATE TABLE audit (
		type_obj int(1) NOT NULL default '0',
		object_id int(10) unsigned NOT NULL default '0',
		user_id int(8) unsigned NOT NULL default '0',
		user_name varchar(20) NOT NULL default '',
		type_modif int(1) NOT NULL default '1',
		quand timestamp(14) NOT NULL
		) 
	*/
	// ---------------------------------------------------------------
	
	var $type_obj;		// Types d'objets audit�s : d�finis dans config.inc.php
						// define('AUDIT_NOTICE'	,    1);
						// define('AUDIT_EXPL'		,    2);
						// define('AUDIT_BULLETIN'	,    3);
						// define('AUDIT_ACQUIS'	,    4);
						// define('AUDIT_PRET'		,    5);
						// define('AUDIT_DEMANDE'	,    14);
						// define('AUDIT_ACTION'	,    15);
						// define('AUDIT_NOTE'		,    16);
	var $object_id;		// id de l'objet audit�
	var $user_id;		// id de l'utilisateur lors de l'insertion dans la table
	var $user_name;		// login de l'utilisateur lors de l'insertion dans la table, permet de conserver un truc m�me apr�s suppression de l'utilisateur
	var $type_modif;	// type de modification : 1 : INSERTION, 2 : MODIFICATION, 3 : MIGRATION
	var $quand;			// timestamp lors de l'insertion dans la table
	var $all_audit;		// tableau de toutes les lignes d'audit de l'objet
	var $info;			// info compl�mentaire � m�moriser
	/*
	Variables globales n�c�ssaires 
		$dbh			Acc�s � la base de donn�es MySQL de PMB
		$PMBuserid		id de l'utilisateur PMB
		$PMBusername	login de l'utilisateur PMB
		$pmb_type_audit	param�tres de PMB sur l'audit : 0 : aucun, 1 cr�ation et derni�re modif, 2 : cr�ation et toutes modifs 
		
	Variables pass�es aux diff�rentes m�thodes selon les besoins
		type_obj
		object_id
		type_modif	 
				
	M�thodes : 
		audit			constructeur : ne fait rien 
							re�oit en param�tres : type_obj et object_id
		get_all			retourne un tableau contenant les infos d'audit de l'objet en fonction de pmb_type_audit
		get_creation	retourne un tableau contenant les infos de cr�ation de l'objet 
		get_last		retourne un tableau contenant les infos de la derni�re modif de l'objet
		
		insert_creation	insert la ligne d'audit de la cr�ation de l'objet
							re�oit en param�tres : type_obj et object_id
		insert_modif	insert une ligne d'audit de modification de l'objet
							re�oit en param�tres : type_obj et object_id
							
		delete_audit	delete toutes les lignes d'audit de l'objet
							re�oit en param�tres : type_obj et object_id
		
	*/	
	// ---------------------------------------------------------------
	//		audit($type, $obj) : constructeur
	// ---------------------------------------------------------------
	function audit ($type=0, $obj=0) {
		global $pmb_type_audit ; 
		if (!$pmb_type_audit) return 0;
		$this->type_obj = $type ;
		$this->object_id = $obj ;
		$this->all_audit=array() ;
		$this->info=array() ;
		$this->info['fields']=array(); 
	}
	
	// ---------------------------------------------------------------
	//		get_all () : r�cup�ration toutes informations
	// ---------------------------------------------------------------
	function get_all() {
		global $dbh, $pmb_type_audit, $msg ;
		if (!$pmb_type_audit) return 0;
		$query = "select user_id, user_name, type_modif, quand, date_format(quand, '".$msg["format_date_heure"]."') as aff_quand, concat(prenom, ' ', nom) as prenom_nom ,info from audit left join users on user_id=userid where ";
		$query .= "type_obj='$this->type_obj' AND ";
		$query .= "object_id='$this->object_id' ";
		$query .= "order by quand ";
		$result = @pmb_mysql_query($query, $dbh);
		if(!$result) die("can't select from table audit left join users :<br /><b>$query</b> ");
		while ($audit=pmb_mysql_fetch_object($result)) {
			$this->all_audit[] = $audit ; 
		}
	}

	// ---------------------------------------------------------------
	//		get_creation () : r�cup�ration cr�ation
	// ---------------------------------------------------------------
	function get_creation () {
		global $dbh, $pmb_type_audit ;
		if (!$pmb_type_audit) return 0;
		return $this->all_audit[0];
	}
	
	// ---------------------------------------------------------------
	//		get_last () : r�cup�ration derni�re modification
	// ---------------------------------------------------------------
	function get_last () {
		global $dbh, $pmb_type_audit ;
		if (!$pmb_type_audit) return 0;
		return $this->all_audit[(count($this->all_audit)-1)];
	}
	
	// ---------------------------------------------------------------
	//		insert_creation ($type=0, $obj=0) : 
	// ---------------------------------------------------------------
	static function insert_creation ($type=0, $obj=0,$info="") {
		global $dbh, $PMBuserid, $PMBusername, $pmb_type_audit ;
		
		if (!$pmb_type_audit) return 0;
		$query = "INSERT INTO audit SET ";
		$query .= "type_obj='$type', ";
		$query .= "object_id='$obj', ";
		$query .= "user_id='$PMBuserid', ";
		$query .= "user_name='$PMBusername', ";
		$query .= "type_modif=1, ";
		$query .= "info='".addslashes($info)."' ";
		$result = @pmb_mysql_query($query, $dbh);
		if(!$result) die("can't INSERT into table audit :<br /><b>$query</b> ");
		return 1;
	}

	// ---------------------------------------------------------------
	//		insert_modif ($type=0, $obj=0) : 
	// ---------------------------------------------------------------
	static function insert_modif ($type=0, $obj=0,$info="") {
		global $dbh, $PMBuserid, $PMBusername, $pmb_type_audit ;
		
		if (!$pmb_type_audit) return 0;
		if ($pmb_type_audit=='1') {
			$query = "DELETE FROM audit WHERE ";
			$query .= "type_obj='$type' AND ";
			$query .= "object_id='$obj' AND ";
			$query .= "type_modif=2 ";
			$result = @pmb_mysql_query($query, $dbh);
			if(!$result) die("can't DELETE FROM table audit :<br /><b>$query</b> ");
		}
		$query = "INSERT INTO audit SET ";
		$query .= "type_obj='$type', ";
		$query .= "object_id='$obj', ";
		$query .= "user_id='$PMBuserid', ";
		$query .= "user_name='$PMBusername', ";
		$query .= "type_modif=2, ";
		$query .= "info='".addslashes($info)."' ";
		$result = @pmb_mysql_query($query, $dbh);
		return 1;
	}
		
	// ---------------------------------------------------------------
	//		delete_audit ($type=0, $obj=0) : 
	// ---------------------------------------------------------------
	static function delete_audit ($type=0, $obj=0) {
		global $dbh ;
		
		$query = "DELETE FROM audit WHERE ";
		$query .= "type_obj='$type' AND ";
		$query .= "object_id in ($obj) ";
		$result = @pmb_mysql_query($query, $dbh);
		return 1;
	}
	
	// ---------------------------------------------------------------
	//		A appeler avant l'update de la table pour m�moriser les valeurs
	// ---------------------------------------------------------------	
	function get_old_infos($requete){
		global $dbh ;
		
		$old_data=array();
		if($this->info['fields'])
		foreach($this->info['fields'] as $field =>$value){
			$this->info['fields'][$field]['old']="";
		}
		$res = pmb_mysql_query($requete, $dbh);
		if (($line = pmb_mysql_fetch_array($res))) {
			$old_data=$line;
		}
		foreach($old_data as $field =>$value){
			if(is_numeric($field)) continue;
			$this->info['fields'][$field]['old']=$value;
		}
	}
	
	// ---------------------------------------------------------------
	//		A appeler apr�s l'update de la table pour m�moriser les valeurs
	// ---------------------------------------------------------------
	function get_new_infos($requete){
		global $dbh ;
		
		$old_data=array();
		if($this->info['fields'])
		foreach($this->info['fields'] as $field =>$value){
			$this->info['fields'][$field]['new']="";
		}
		$res = pmb_mysql_query($requete, $dbh);
		if (($line = pmb_mysql_fetch_array($res))) {
			$old_data=$line;
		}
		$i=0;
		foreach($old_data as $field =>$value){
			if(is_numeric($field)) continue;
			$this->info['fields'][$field]['new']=$value;
		}		
	}
	// ---------------------------------------------------------------
	//		Ins�re l'audit avec les champs modifi�s
	// ---------------------------------------------------------------
	function save_info_modif($type=0, $obj=0,$comment=""){
		
		$info['comment']=$comment;
		foreach($this->info['fields'] as $field =>$value){
			// on ne garde que les champs modifi�s
			if($this->info['fields'][$field]['old']==$this->info['fields'][$field]['new'])continue;
			$info['fields'][$field]['old']=$this->info['fields'][$field]['old'];
			$info['fields'][$field]['new']=$this->info['fields'][$field]['new'];			
		}
		$this->insert_modif ($type, $obj, json_encode($info));
	}
	
} // fin if !define 
} // class audit


