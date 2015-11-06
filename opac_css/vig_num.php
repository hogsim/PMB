<?php
// +-------------------------------------------------+
// © 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: vig_num.php,v 1.13.10.2 2015-06-05 11:30:18 jpermanne Exp $

$base_path=".";
require_once($base_path."/includes/init.inc.php");

// définition du minimum nécéssaire 
require_once($base_path."/includes/error_report.inc.php") ;

// récupération paramètres MySQL et connection á la base
require_once($base_path.'/includes/opac_db_param.inc.php');
require_once($base_path.'/includes/opac_mysql_connect.inc.php');
$dbh = connection_mysql();

//Sessions !! Attention, ce doit être impérativement le premer include (à cause des cookies)
require_once($base_path."/includes/session.inc.php");
require_once($base_path."/includes/global_vars.inc.php");
require_once($base_path.'/includes/opac_config.inc.php');

if ($css=="") $css=1;

require_once($base_path.'/includes/start.inc.php');

require_once($base_path."/includes/check_session_time.inc.php");

// récupération localisation
require_once($base_path.'/includes/localisation.inc.php');

// version actuelle de l'opac
require_once($base_path.'/includes/opac_version.inc.php');

require_once($include_path."/explnum.inc.php");

$explnum_id = $explnum_id+0;
$resultat = mysql_query("SELECT explnum_id, explnum_mimetype, explnum_vignette,explnum_extfichier FROM explnum WHERE explnum_id = '$explnum_id' ", $dbh);
$nb_res = mysql_num_rows($resultat) ;

if (!$nb_res) {
	exit ;
} 

$ligne = mysql_fetch_object($resultat);
if ($ligne->explnum_vignette) {
	print $ligne->explnum_vignette;
	exit ;
} else {
	create_tableau_mimetype();
	$iconname=icone_mimetype ($ligne->explnum_mimetype, $ligne->explnum_extfichier);
	$fp = fopen("./images/mimetype/$iconname" , "r" ) ;
	$contenu_vignette = fread ($fp, filesize("./images/mimetype/$iconname"));
	fclose ($fp) ;
	print $contenu_vignette ;
}
