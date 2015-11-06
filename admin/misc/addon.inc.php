<?php
// +-------------------------------------------------+
//  2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: addon.inc.php,v 1.5.4.8 2015-10-15 09:35:03 jpermanne Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

function traite_rqt($requete="", $message="") {

	global $dbh,$charset;
	$retour="";
	if($charset == "utf-8"){
		$requete=utf8_encode($requete);
	}
	$res = pmb_mysql_query($requete, $dbh) ;
	$erreur_no = pmb_mysql_errno();
	if (!$erreur_no) {
		$retour = "Successful";
	} else {
		switch ($erreur_no) {
			case "1060":
				$retour = "Field already exists, no problem.";
				break;
			case "1061":
				$retour = "Key already exists, no problem.";
				break;
			case "1091":
				$retour = "Object already deleted, no problem.";
				break;
			default:
				$retour = "<font color=\"#FF0000\">Error may be fatal : <i>".pmb_mysql_error()."<i></font>";
				break;
			}
	}
	return "<tr><td><font size='1'>".($charset == "utf-8" ? utf8_encode($message) : $message)."</font></td><td><font size='1'>".$retour."</font></td></tr>";
}
echo "<table>";

/******************** AJOUTER ICI LES MODIFICATIONS *******************************/

switch ($pmb_bdd_subversion) {
	case '0' :
		// DB - Modification de la table resarc (id resa_planning pour resa issue d'une prévision)
		$rqt = "alter table resa_archive add resarc_resa_planning_id_resa int(8) unsigned not null default 0";
		echo traite_rqt($rqt,"alter resa_archive add resarc_resa_planning_id_resa");
	case '1' :
		//DG - Champs perso demandes
		$rqt = "create table if not exists demandes_custom (
				idchamp int(10) unsigned NOT NULL auto_increment,
				name varchar(255) NOT NULL default '',
				titre varchar(255) default NULL,
				type varchar(10) NOT NULL default 'text',
				datatype varchar(10) NOT NULL default '',
				options text,
				multiple int(11) NOT NULL default 0,
				obligatoire int(11) NOT NULL default 0,
				ordre int(11) default NULL,
				search INT(1) unsigned NOT NULL DEFAULT 0,
				export INT(1) unsigned NOT NULL DEFAULT 0,
				exclusion_obligatoire INT(1) unsigned NOT NULL DEFAULT 0,
				pond int not null default 100,
				opac_sort INT NOT NULL DEFAULT 0,
				PRIMARY KEY  (idchamp)) ";
		echo traite_rqt($rqt,"create table if not exists demandes_custom ");
		
		$rqt = "create table if not exists demandes_custom_lists (
				demandes_custom_champ int(10) unsigned NOT NULL default 0,
				demandes_custom_list_value varchar(255) default NULL,
				demandes_custom_list_lib varchar(255) default NULL,
				ordre int(11) default NULL,
				KEY i_demandes_custom_champ (demandes_custom_champ),
				KEY i_demandes_champ_list_value (demandes_custom_champ,demandes_custom_list_value)) " ;
		echo traite_rqt($rqt,"create table if not exists demandes_custom_lists ");
		
		$rqt = "create table if not exists demandes_custom_values (
				demandes_custom_champ int(10) unsigned NOT NULL default 0,
				demandes_custom_origine int(10) unsigned NOT NULL default 0,
				demandes_custom_small_text varchar(255) default NULL,
				demandes_custom_text text,
				demandes_custom_integer int(11) default NULL,
				demandes_custom_date date default NULL,
				demandes_custom_float float default NULL,
				KEY i_demandes_custom_champ (demandes_custom_champ),
				KEY i_demandes_custom_origine (demandes_custom_origine)) " ;
		echo traite_rqt($rqt,"create table if not exists demandes_custom_values ");
		
	case '2' :
		// NG - Circulation simplifiée de périodique
		$rqt = "ALTER TABLE serialcirc ADD serialcirc_simple int unsigned not null default 0" ;
		echo traite_rqt($rqt,"ALTER TABLE serialcirc ADD serialcirc_simple ");
		
		// NG - Script de construction d'étiquette de circulation simplifiée de périodique
		if (pmb_mysql_num_rows(pmb_mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='serialcirc_simple_print_script' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
					VALUES (0, 'pmb', 'serialcirc_simple_print_script', '', 'Script de construction d\'étiquette de circulation simplifiée de périodique' ,'',0)";
			echo traite_rqt($rqt,"insert pmb_serialcirc_simple_print_script into parametres");
		}
		
	case '3' :
		// AP - Nombre maximum de notices à afficher dans une liste sans pagination
		if (pmb_mysql_num_rows(pmb_mysql_query("select 1 from parametres where type_param= 'opac' and sstype_param='max_results_on_a_page' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
				VALUES (0, 'opac', 'max_results_on_a_page', '500', 'Nombre maximum de notices à afficher sur une page, utile notamment quand la navigation est désactivée' ,'d_aff_recherche',0)";
			echo traite_rqt($rqt,"insert max_results_on_a_page into parametres");
		}
	case '4' :
		//JP - taille de certains champs blob trop juste
		$rqt = "ALTER TABLE opac_sessions CHANGE session session MEDIUMBLOB NULL DEFAULT NULL";
		echo traite_rqt($rqt,"ALTER TABLE opac_sessions CHANGE session MEDIUMBLOB");
		$rqt = " select 1 " ;
		echo traite_rqt($rqt,"<b><a href='".$base_path."/admin.php?categ=netbase' target=_blank>VOUS DEVEZ FAIRE UN NETOYAGE DE BASE (APRES ETAPES DE MISE A JOUR) / YOU MUST DO A DATABASE CLEANUP (STEPS AFTER UPDATE) : Admin > Outils > Nettoyage de base</a></b> ") ;
	case '5' :
		//JP - bouton vider le cache portail
		$rqt = "ALTER TABLE cms_articles ADD article_update_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL";
		echo traite_rqt($rqt,"ALTER TABLE cms_articles ADD article_update_timestamp");
		$rqt = "UPDATE cms_articles SET article_update_timestamp=article_creation_date";
		echo traite_rqt($rqt,"UPDATE cms_articles SET article_update_timestamp");
		$rqt = "ALTER TABLE cms_sections ADD section_update_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL";
		echo traite_rqt($rqt,"ALTER TABLE cms_sections ADD section_update_timestamp");
		$rqt = "UPDATE cms_sections SET section_update_timestamp=section_creation_date";
		echo traite_rqt($rqt,"UPDATE cms_sections SET section_update_timestamp");
	case '6' :
		//JP - choix notice nouveauté oui/non par utilisateur en création de notice
		$rqt = "ALTER TABLE users ADD deflt_notice_is_new INT( 1 ) UNSIGNED NOT NULL DEFAULT '0'";
		echo traite_rqt($rqt,"ALTER TABLE users ADD deflt_notice_is_new");
		
}

/******************** JUSQU'ICI **************************************************/
/* PENSER à faire +1 au paramètre $pmb_subversion_database_as_it_shouldbe dans includes/config.inc.php */
/* COMMITER les deux fichiers addon.inc.php ET config.inc.php en même temps */

echo traite_rqt("update parametres set valeur_param='".$pmb_subversion_database_as_it_shouldbe."' where type_param='pmb' and sstype_param='bdd_subversion'","Update to $pmb_subversion_database_as_it_shouldbe database subversion.");
echo "<table>";