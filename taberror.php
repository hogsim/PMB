<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: taberror.php,v 1.1.2.1 2015-03-20 10:26:07 jpermanne Exp $

// d�finition du minimum n�c�ssaire 
$base_path=".";                            
$base_auth = "";  
$base_title = "";
$base_use_dojo=1;

require_once ("$base_path/includes/init.inc.php");  

print "<div id='att' style='z-Index:1000'></div>";

print $menu_bar;
print $extra;
print $extra2;
print $extra_info;
if($use_shortcuts) {
	include("$include_path/shortcuts/circ.sht");
}
print "<div id='conteneur' class='circ'>
		<div id='contenu'>";

error_message("tt",$msg["12"]);

print "</div>
</div>";

print $footer;
mysql_close($dbh);