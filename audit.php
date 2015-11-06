<?php
// +-------------------------------------------------+
// © 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: audit.php,v 1.4.10.3 2015-05-06 08:10:45 jpermanne Exp $

// définition du minimum nécéssaire 
$base_path=".";                            
$base_auth = "CATALOGAGE_AUTH";  
$base_title = "\$msg[audit_titre]";

require_once ("$base_path/includes/init.inc.php");  

switch($pmb_type_audit) {
	case '1':
		$audit = new audit($type_obj, $object_id) ;
		$audit->get_all();
		if(count($audit->all_audit) == 1){
			$all[0] =  $audit->get_creation() ;
		} else {
			$all[0] =  $audit->get_creation() ;
			$all[1] =  $audit->get_last() ;
		}		
		break;
	case '2':
		$audit = new audit($type_obj, $object_id) ;
		$audit->get_all() ;
		$all = $audit->all_audit ;
		break;
	default:
	case '0':
		echo "<script> self.close(); </script>" ;
		break;
	}

$audit_list = "<script type='text/javascript' src='./javascript/sorttable.js'></script>
<table class='sortable' ><tr><th>".$msg['audit_col_userid']."</th><th>".$msg['audit_col_username']."</th><th>".$msg['audit_col_type_action']."</th><th>".$msg['audit_col_date_heure']."</th><th>".$msg['audit_col_nom']."</th><th>".$msg['audit_comment']."</th></tr>";
while(list($cle, $valeur) = each($all)) {
	//user_id, user_name, type_modif, quand, concat(prenom, ' ', nom) as prenom_nom
	$info=json_decode($valeur->info);
	$info_display="";
	if(is_object($info)){
		if($info->comment)$info_display.=$info->comment."<br>";
		if(count($info->fields)){
			foreach($info->fields as $fieldname => $values){
				if(is_object($values)){
					$info_display.=$fieldname." : ".$values->old." => ".$values->new."<br>";
				}
			}
		}
	}else $info_display=$valeur->info;
	
	$audit_list .= "
		<tr>
			<td>$valeur->user_id</td>
			<td>$valeur->user_name</td>
			<td>".$msg['audit_type'.$valeur->type_modif]."</td>
			<td>$valeur->aff_quand</td>
			<td>$valeur->prenom_nom</td>
			<td>".$info_display."</td>
			</tr>";
		}
$audit_list .= "</table>";

echo $audit_list ;
mysql_close($dbh);
