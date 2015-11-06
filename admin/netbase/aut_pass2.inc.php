<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: aut_pass2.inc.php,v 1.15 2015-04-03 11:16:18 jpermanne Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

// la taille d'un paquet de notices
$lot = AUTHOR_PAQUET_SIZE; // defini dans ./params.inc.php

// taille de la jauge pour affichage
$jauge_size = GAUGE_SIZE;

// initialisation de la borne de d�part
if(!isset($start)) $start=0;

$v_state=urldecode($v_state);

print "<br /><br /><h2 align='center'>".htmlentities($msg["nettoyage_renvoi_auteurs"], ENT_QUOTES, $charset)."</h2>";

$query = pmb_mysql_query("update authors A1 left join authors A2 on A1.author_see=A2.author_id set A1.author_see=0 where A2.author_id is null");
$affected += pmb_mysql_affected_rows();

$v_state .= "<br /><img src=../../images/d.gif hspace=3>".htmlentities($msg["nettoyage_suppr_auteurs"], ENT_QUOTES, $charset)." : ";
$v_state .= $affected." ".htmlentities($msg["nettoyage_res_suppr_auteurs"], ENT_QUOTES, $charset);
$opt = pmb_mysql_query('OPTIMIZE TABLE authors');
// mise � jour de l'affichage de la jauge
print "<table border='0' align='center' width='$table_size' cellpadding='0'><tr><td class='jauge'>
    	<img src='../../images/jauge.png' width='$jauge_size' height='16'></td></tr></table>
  		<div align='center'>100%</div>";
print "
	<form class='form-$current_module' name='process_state' action='./clean.php' method='post'>
		<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
		<input type='hidden' name='spec' value=\"$spec\">
		<input type='hidden' name='affected' value=\"$affected\">
		<input type='hidden' name='pass2' value=\"2\">			
	</form>
	<script type=\"text/javascript\"><!--
		document.forms['process_state'].submit();
		-->
		</script>";
