<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: gen_code_exemplaire.php,v 1.5.6.1 2014-04-15 10:14:14 dgoron Exp $

function init_gen_code_exemplaire($notice_id,$bull_id) {
	global $dbh;
	$requete="select max(expl_cb)as cb from exemplaires WHERE expl_cb like 'GEN%'";
	$query = mysql_query($requete, $dbh);
	if(mysql_num_rows($query)) {	
    	if(($cb = mysql_fetch_object($query)))
			$code_exemplaire= $cb->cb;
		else $code_exemplaire = "GEN000000"; 	
	} else $code_exemplaire = "GEN000000"; 
	return $code_exemplaire;  	   						
}

function gen_code_exemplaire($notice_id,$bull_id,$code_exemplaire) {
	if(preg_match("/(\D*)([0-9]*)/",$code_exemplaire,$matches)){
		$len = strlen($matches[2]);
		$matches[2]++;
		$code_exemplaire=$matches[1].str_pad($matches[2],$len,"0",STR_PAD_LEFT);
	} else{
		$code_exemplaire++;
	}
	return $code_exemplaire;
}