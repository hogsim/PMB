<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: procs.inc.php,v 1.24 2015-04-03 11:16:21 jpermanne Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

require_once ($include_path."/templates/procs_exp_imp.tpl.php");
require_once ($include_path."/procs_exp_imp.inc.php");

print "<script type='text/javascript'>
function test_form(form) {
	if(form.f_proc_name.value.length == 0) {
		alert(\"$msg[702]\");
		form.f_proc_name.focus();
		return false;
		}
	if(form.f_proc_code.value.length == 0) {
		alert(\"$msg[703]\");
		form.f_proc_code.focus();
		return false;
		}
	return true;
	}
</script>";

function check_param($requete) {
	$query_parameters=array();
	//S'il y a des termes !!*!! dans la requ�te alors il y a des param�tres
	if (preg_match_all("|!!(.*)!!|U",$requete,$query_parameters)) {
			for ($i=0; $i<count($query_parameters[1]); $i++) {
				if (!preg_match("/^[A-Za-z][A-Za-z0-9_]*$/",$query_parameters[1][$i])) {
					 "(".$query_parameters[1][$i].")";
				}
			}
	}
	return true;
}

function show_procs($dbh) {
	global $msg;
	global $PMBuserid;
	print "<hr /><table>";

	// affichage du tableau des proc�dures
	if ($PMBuserid!=1) $where=" where (autorisations='$PMBuserid' or autorisations like '$PMBuserid %' or autorisations like '% $PMBuserid %' or autorisations like '% $PMBuserid') ";
	$requete = "SELECT idproc, type, name, requete, comment, autorisations FROM caddie_procs $where ORDER BY type, name ";
	$res = pmb_mysql_query($requete, $dbh);

	$nbr = pmb_mysql_num_rows($res);

	$parity=1;
	for($i=0;$i<$nbr;$i++) {
		$row=pmb_mysql_fetch_row($res);
		$rqt_autorisation=explode(" ",$row[5]);
		if (array_search ($PMBuserid, $rqt_autorisation)!==FALSE || $PMBuserid == 1) {
			if ($parity % 2) {
				$pair_impair = "even";
				} else {
					$pair_impair = "odd";
					}
			$parity += 1;
			$action=" onmousedown=\"document.location='./catalog.php?categ=caddie&sub=gestion&quoi=procs&action=modif&id=$row[0]';\"";
	        	$tr_javascript=" onmouseover=\"this.className='surbrillance'\" onmouseout=\"this.className='$pair_impair'\" ";
        		print "<tr class='$pair_impair' $tr_javascript style='cursor: pointer'>";
				if ($row[1]!="ACTION"){
					print "	<td width='10'><input class='bouton' type='button' value=' $msg[procs_options_tester_requete] ' onClick=\"document.location='./catalog.php?categ=caddie&sub=gestion&quoi=procs&action=execute&id=$row[0]'\" />";
				}else{
					print "	<td width='10' $action>&nbsp;";
				}
				print pmb_bidi("
					</td>
					<td width='80' $action>
						$row[1]
						</td>
					<td $action>
						<strong>$row[2]</strong><br />
						<small>$row[4]&nbsp;</small>
						</td>");
				if (preg_match_all("|!!(.*)!!|U",$row[3],$query_parameters)){
					print "<td><a href='catalog.php?categ=caddie&sub=gestion&quoi=procs&action=configure&id_query=".$row[0]."'>$msg[procs_options_config_param]</a>";
				}else{
					print "<td $action>&nbsp;";
				}
				print "</td>" ;
				print "<td><input class='bouton' type='button' value=\"".$msg[procs_bt_export]."\" onClick=\"document.location='./export.php?quoi=procs&sub=caddie&id=$row[0]'\" /></td>
						</tr>";
			}
		}
	print "</table><hr />
		<input class='bouton' type='button' value=' $msg[704] ' onClick=\"document.location='./catalog.php?categ=caddie&sub=gestion&quoi=procs&action=add'\" />
		<input class='bouton' type='button' value=' $msg[procs_bt_import] ' onClick=\"document.location='./catalog.php?categ=caddie&sub=gestion&quoi=procs&action=import'\" />";
	}

function proc_form($name='', $code='', $comment='', $id=0, $autorisations=array(), $type="SELECT" ) {
	global $msg;
	global $cart_procs_form;
	global $cart_procs_edit_form ; 
	global $charset;

	if ($id) {
		$cart_procs_form = $cart_procs_edit_form ; 
		if ($type!="ACTION") $cart_procs_form=str_replace("!!exec_button!!","<input type='button' class='bouton' value=' $msg[708] ' onClick=\"document.location='./catalog.php?categ=caddie&sub=gestion&quoi=procs&action=execute&id=!!id!!'\" />&nbsp;",$cart_procs_form); else $cart_procs_form=str_replace("!!exec_button!!","",$cart_procs_form);
	}
	$cart_procs_form = str_replace('!!id!!', $id, $cart_procs_form);
	if(!$id) $cart_procs_form = str_replace('!!form_title!!', $msg[704], $cart_procs_form);
		else $cart_procs_form = str_replace('!!form_title!!', $msg["procs_modification"], $cart_procs_form);

	if($id && $name && $code) $action = "./catalog.php?categ=caddie&sub=gestion&quoi=procs&action=modif&id=$id";
		else $action = "./catalog.php?categ=caddie&sub=gestion&quoi=procs&action=add";
	$cart_procs_form = str_replace('!!action!!', $action, $cart_procs_form);
	
 	$cart_procs_form = str_replace('!!type!!', htmlentities($msg["caddie_procs_type_".$type],ENT_QUOTES, $charset), $cart_procs_form);
	$cart_procs_form = str_replace('!!name!!', htmlentities($name,ENT_QUOTES, $charset), $cart_procs_form);
 	$cart_procs_form = str_replace('!!name_suppr!!', htmlentities(addslashes($name),ENT_QUOTES, $charset), $cart_procs_form);
 	$cart_procs_form = str_replace('!!code!!', htmlentities($code,ENT_QUOTES, $charset), $cart_procs_form);
 	$cart_procs_form = str_replace('!!comment!!', htmlentities($comment,ENT_QUOTES, $charset), $cart_procs_form);
	
	$autorisations_users="";
	$id_check_list='';
	while (list($row_number, $row_data) = each($autorisations)) {
		$id_check="auto_".$row_data[1];
		if($id_check_list)$id_check_list.='|';
		$id_check_list.=$id_check;
		if ($row_data[0]) $autorisations_users.="<span class='usercheckbox'><input type='checkbox' name='userautorisation[]' id='$id_check' value='".$row_data[1]."' checked class='checkbox'><label for='$id_check' class='normlabel'>&nbsp;".$row_data[2]."</label></span>&nbsp;&nbsp;";
		else $autorisations_users.="<span class='usercheckbox'><input type='checkbox' name='userautorisation[]' id='$id_check' value='".$row_data[1]."' class='checkbox'><label for='$id_check' class='normlabel'>&nbsp;".$row_data[2]."</label></span>&nbsp;&nbsp;";		
	}
	$autorisations_users.="<input type='hidden' id='auto_id_list' name='auto_id_list' value='$id_check_list' >";	
	$cart_procs_form = str_replace('!!autorisations_users!!', $autorisations_users, $cart_procs_form);
	
	print confirmation_delete("./catalog.php?categ=caddie&sub=gestion&quoi=procs&action=del&id=");
	print $cart_procs_form;
	}

function run_form($id, $dbh) {
	global $msg;
	global $charset;
	
	$hp=new parameters($id);
	if (preg_match_all("|!!(.*)!!|U",$hp->proc->requete,$query_parameters))
		$hp->gen_form("catalog.php?categ=caddie&sub=gestion&quoi=procs&action=final&id=$id");
	else echo "<script>document.location='catalog.php?categ=caddie&sub=gestion&quoi=procs&action=final&id=$id'</script>";
	}


switch($action) {
	case 'configure':
		$hp=new parameters($id_query);
		$hp->show_config_screen("catalog.php?categ=caddie&sub=gestion&quoi=procs&action=update_config","catalog.php?categ=caddie&sub=gestion&quoi=procs");
		break;
	case 'update_config':
		$hp=new parameters($id_query);
		$hp->update_config("catalog.php?categ=caddie&sub=gestion&quoi=procs");
		break;
	case 'final':
		$hp=new parameters($id_query);
		if (preg_match_all("|!!(.*)!!|U",$hp->proc->requete,$query_parameters)) {
			$hp->get_final_query();
			$code=$hp->final_query;
			$id=$id_query;
		}
		include("./catalog/caddie/gestion/execute.inc.php");
		break;
	case 'execute':
		// form pour params et validation
		run_form($id, $dbh);
		break;
	case 'modif':
		if($id) {
			if($f_proc_name && $f_proc_code) {
				// faire la modification
				if (is_array($userautorisation)) $autorisations=implode(" ",$userautorisation);
				else $autorisations="";
				$param_name=check_param($f_proc_code);
				if ($param_name!==true) {
					error_message_history($param_name, sprintf($msg["proc_param_check_field_name"],$param_name), 1); 
					exit();
				}
				$requete = "UPDATE caddie_procs SET name='$f_proc_name',requete='$f_proc_code',comment='$f_proc_comment' , autorisations='$autorisations' WHERE idproc=$id ";
				$res = pmb_mysql_query($requete, $dbh);
				show_procs($dbh);
			} else {
				// afficher le form avec les bonnes valeurs
				$requete = "SELECT idproc, name, requete, comment, autorisations, type FROM caddie_procs WHERE idproc=$id LIMIT 1 ";
				$res = pmb_mysql_query($requete, $dbh);
				$requete_users = "SELECT userid, username FROM users order by username ";
				$res_users = pmb_mysql_query($requete_users, $dbh);
				$all_users=array();
				while (list($all_userid,$all_username)=pmb_mysql_fetch_row($res_users)) {
					$all_users[]=array($all_userid,$all_username);
					}
				if(pmb_mysql_num_rows($res)) {
					$row = pmb_mysql_fetch_row($res);
					$autorisations_donnees=explode(" ",$row[4]);
					for ($i=0 ; $i<count($all_users) ; $i++) {
						if (array_search ($all_users[$i][0], $autorisations_donnees)!==FALSE) $autorisation[$i][0]=1;
						else $autorisation[$i][0]=0;
						$autorisation[$i][1]= $all_users[$i][0];
						$autorisation[$i][2]= $all_users[$i][1];
					}
					proc_form($row[1], $row[2], $row[3], $row[0],$autorisation, $row[5]);
				}
			}
		} else {
			show_procs($dbh);
		}
		break;
	case 'add':
		if($f_proc_name && $f_proc_code) {
			$requete = "SELECT count(1) FROM caddie_procs WHERE name='$f_proc_name' ";
			$res = pmb_mysql_query($requete, $dbh);
			$nbr_lignes = pmb_mysql_result($res, 0, 0);
			if(!$nbr_lignes) {
				if (is_array($userautorisation)) {
					$autorisations=implode(" ",$userautorisation);
				} else {
					$autorisations='';
				}
				$param_name=check_param($f_proc_code);
				if ($param_name!==true) {
					error_message_history($param_name, sprintf($msg["proc_param_check_field_name"],$param_name), 1);
					exit();
				}
				$requete = "INSERT INTO caddie_procs (idproc,type,name,requete,comment,autorisations) VALUES ('', '$f_proc_type', '$f_proc_name', '$f_proc_code', '$f_proc_comment', '$autorisations' ) ";
				$res = pmb_mysql_query($requete, $dbh);
				} else {
					print "<script language='Javascript'>alert(\"$msg[709]\");</script>";
					}
			show_procs($dbh);
		} else {
			$requete_users = "SELECT userid, username FROM users order by username ";
			$res_users = pmb_mysql_query($requete_users, $dbh);
			$autorisation=array();
			while (list($all_userid,$all_username)=pmb_mysql_fetch_row($res_users)) {
				if ($all_userid==1 or $all_userid==$PMBuserid) $autorisation[]=array(1,$all_userid,$all_username);
					else $autorisation[]=array(0,$all_userid,$all_username);
				}
			proc_form("", "", "", 0, $autorisation);
		}
		break;
	case 'import':
		$import_proc_tmpl = str_replace("!!action!!", "./catalog.php?categ=caddie&sub=gestion&quoi=procs&action=importsuite", $import_proc_tmpl);
		print $import_proc_tmpl ;
		break;
	case 'importsuite':
		procs_create ("CADDIE", "./catalog.php?categ=caddie&sub=gestion&quoi=procs&action=modif&id=!!id!!", "./catalog.php?categ=caddie&sub=gestion&quoi=procs&action=import") ;
		break;
	case 'del':
		if($id) {
			$requete = "DELETE FROM caddie_procs WHERE idproc=$id ";
			$res = pmb_mysql_query($requete, $dbh);
			$requete = "OPTIMIZE TABLE caddie_procs ";
			$res = pmb_mysql_query($requete, $dbh);
			show_procs($dbh);
		}
		break;
	default:
		show_procs($dbh);
		break;
	}
