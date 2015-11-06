<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: reindex_cms.inc.php,v 1.1.4.2 2014-04-04 09:26:08 arenou Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

require_once($base_path.'/classes/cms/cms_article.class.php');
require_once($base_path.'/classes/cms/cms_section.class.php');

// la taille d'un paquet de notices
$lot = REINDEX_PAQUET_SIZE; // defini dans ./params.inc.php

// taille de la jauge pour affichage
$jauge_size = GAUGE_SIZE;
$jauge_size .= "px";

// initialisation de la borne de d�part
if (!isset($start)) {
	$start=$start_2=0;
	//remise a zero de la table au d�but
	mysql_query("TRUNCATE cms_editorial_words_global_index",$dbh);
	mysql_query("ALTER TABLE cms_editorial_words_global_index DISABLE KEYS",$dbh);
	
	mysql_query("TRUNCATE cms_editorial_fields_global_index",$dbh);
	mysql_query("ALTER TABLE cms_editorial_fields_global_index DISABLE KEYS",$dbh);
}

$v_state=urldecode($v_state);

if (!$count) {
	$notices = mysql_query("SELECT count(1) FROM cms_articles", $dbh);
	$count = mysql_result($notices, 0, 0);
	$notices = mysql_query("SELECT count(1) FROM cms_sections", $dbh);
	$count+= mysql_result($notices, 0, 0);
}
	
print "<br /><br /><h2 align='center'>".htmlentities($msg["nettoyage_reindex_cms"], ENT_QUOTES, $charset)."</h2>";

$NoIndex = 1;

$query = mysql_query("select id_article from cms_articles order by id_article LIMIT $start, $lot");
if(mysql_num_rows($query)) {
		
	// d�finition de l'�tat de la jauge
	$state = floor(($start+$start_2) / ($count / $jauge_size));
	if(($start+$start_2)>$count){
		$state = floor(($count/2)/($count/$jauge_size));
	}
	$state .= "px";
	// mise � jour de l'affichage de la jauge
	print "<table border='0' align='center' width='$jauge_size' cellpadding='0'><tr><td class='jauge' width='100%'>";
	print "<img src='../../images/jauge.png' width='$state' height='16px'></td></tr></table>";
		
	// calcul pourcentage avancement
	$percent = floor((($start+$start_2)/$count)*100);
	if($percent>100) $percent = 50;
	// affichage du % d'avancement et de l'�tat
	print "<div align='center'>$percent%</div>";
	while($row = mysql_fetch_assoc($query)) {		
		// permet de charger la bonne langue, mot vide...
		$article = new cms_article($row['id_article']);
		$info=$article->maj_indexation();
		}
	mysql_free_result($query);
	
	$next = $start + $lot;
	print "
	<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
	<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
	<input type='hidden' name='spec' value=\"$spec\">
	<input type='hidden' name='start' value=\"$next\">
	<input type='hidden' name='start_2' value=\"$start_2\">
	<input type='hidden' name='count' value=\"$count\">
	</form>
	<script type=\"text/javascript\"><!-- 
	setTimeout(\"document.forms['current_state'].submit()\",1000); 
	-->
	</script>";
} else {
	$query = mysql_query("select id_section from cms_sections order by id_section LIMIT $start_2, $lot");
	if(mysql_num_rows($query)) {
	
		// d�finition de l'�tat de la jauge
		$state = floor(($start+$start_2) / ($count / $jauge_size));
		if(($start+$start_2)>$count){
			$state = floor(($count/2)/($count/$jauge_size));
		}
		$state .= "px";
		// mise � jour de l'affichage de la jauge
		print "<table border='0' align='center' width='$jauge_size' cellpadding='0'><tr><td class='jauge' width='100%'>";
		print "<img src='../../images/jauge.png' width='$state' height='16px'></td></tr></table>";
	
		// calcul pourcentage avancement
		$percent = floor((($start+$start_2)/$count)*100);
	
		if($percent>100) $percent = 50;
		// affichage du % d'avancement et de l'�tat
		print "<div align='center'>$percent%</div>";
	
		while($row = mysql_fetch_assoc($query)) {
			// permet de charger la bonne langue, mot vide...
			$section = new cms_section($row['id_section']);
			$info=$section->maj_indexation();
		}
		mysql_free_result($query);
	
		$next = $start + $lot;
		print "
		<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
		<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
		<input type='hidden' name='spec' value=\"$spec\">
		<input type='hidden' name='start' value=\"$start\">
		<input type='hidden' name='start_2' value=\"$next\">
		<input type='hidden' name='count' value=\"$count\">
		</form>
		<script type=\"text/javascript\"><!--
		setTimeout(\"document.forms['current_state'].submit()\",1000);
		-->
		</script>";
	}else {
	
		$spec = $spec - INDEX_CMS;
		$not = mysql_query("SELECT 1 FROM cms_editorial_words_global_index group by num_obj,type", $dbh);
		$compte = mysql_num_rows($not);
		$v_state .= "<br /><img src=../../images/d.gif hspace=3>".htmlentities($msg["nettoyage_reindex_cms"], ENT_QUOTES, $charset)." :";
		$v_state .= $compte." ".htmlentities($msg["nettoyage_res_reindex_cms"], ENT_QUOTES, $charset);
		print "
			<form class='form-$current_module' name='process_state' action='./clean.php' method='post'>
			<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
			<input type='hidden' name='spec' value=\"$spec\">
			</form>
			<script type=\"text/javascript\"><!--
				document.forms['process_state'].submit();
				-->
			</script>";
		mysql_query("ALTER TABLE cms_editorial_words_global_index ENABLE KEYS",$dbh);
		mysql_query("ALTER TABLE cms_editorial_fields_global_index ENABLE KEYS",$dbh);
	}
}