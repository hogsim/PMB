<?php
// +-------------------------------------------------+
//  2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: addon.inc.php,v 1.3.2.60 2015-10-28 13:03:37 jpermanne Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

function traite_rqt($requete="", $message="") {

	global $dbh,$charset;
	$retour="";
	if($charset == "utf-8"){
		$requete=utf8_encode($requete);
	}
	$res = mysql_query($requete, $dbh) ; 
	$erreur_no = mysql_errno();
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
				$retour = "<font color=\"#FF0000\">Error may be fatal : <i>".mysql_error()."<i></font>";
				break;
			}
	}		
	return "<tr><td><font size='1'>".($charset == "utf-8" ? utf8_encode($message) : $message)."</font></td><td><font size='1'>".$retour."</font></td></tr>";
}
echo "<table>";

/******************** AJOUTER ICI LES MODIFICATIONS *******************************/

switch ($pmb_bdd_subversion) {
	case "0":
		//DB -Ajout d'une fonction spécifique pour génération de code-barres lecteurs
		$rqt = "update parametres set comment_param='Numéro de carte de lecteur automatique ?\n 0: Non (si utilisation de cartes pré-imprimées)\n";
		$rqt.= " 1: Oui, entièrement numérique\n 2,a,b,c: Oui, avec préfixe: a=longueur du préfixe, b=nombre de chiffres de la partie numérique, c=préfixe fixé (facultatif)\n";
		$rqt.= " 3,fonction: fonction de génération spécifique dans fichier nommé de la même façon, à placer dans pmb/circ/empr' ";
		$rqt.= " where type_param='pmb' and sstype_param='num_carte_auto' ";
		echo traite_rqt($rqt,"update parametre pmb_num_carte_auto ");
	case "1":
		//DG - Le super user doit avoir accès à tous les établissements
		$rqt = "UPDATE entites SET autorisations=CONCAT(' 1', autorisations) WHERE type_entite='1' AND autorisations NOT LIKE '% 1 %'";
		echo traite_rqt($rqt, 'UPDATE entites SET autorisations=CONCAT(" 1",autorisations) for super user');
	case "2":
		//DG - Optimisation
		$rqt = "show fields from notices_fields_global_index";
		$res = mysql_query($rqt);
		$exists = false;
		if(mysql_num_rows($res)){
			while($row = mysql_fetch_object($res)){
				if($row->Field == "authority_num"){
					$exists = true;
					break;
				}
			}
		}
		if(!$exists){
			if (mysql_result(mysql_query("select count(*) from notices"),0,0) > 15000){
				$rqt = "truncate table notices_fields_global_index";
				echo traite_rqt($rqt,"truncate table notices_fields_global_index");
		
				// Info de réindexation
				$rqt = " select 1 " ;
				echo traite_rqt($rqt,"<b><a href='".$base_path."/admin.php?categ=netbase' target=_blank>VOUS DEVEZ REINDEXER / YOU MUST REINDEX : Admin > Outils > Nettoyage de base</a></b> ") ;
			}
			//JP - Synchronisation RDF
			$rqt = "ALTER TABLE notices_fields_global_index ADD authority_num VARCHAR(50) NOT NULL DEFAULT '0'";
			echo traite_rqt($rqt,"alter table notices_fields_global_index add authority_num");
		}
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='synchro_rdf' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion) VALUES (0, 'pmb', 'synchro_rdf', '0', 'Activer la synchronisation rdf\n 0 : non \n 1 : oui (l\'activation de ce paramètre nécessite une ré-indexation)','',0) " ;
			echo traite_rqt($rqt,"insert opac_bannette_priv_periodicite = 15 into parametres");
		}
	case "3":
		//DB - Génération code-barres pour les inscritions Web
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'opac' and sstype_param='websubscribe_num_carte_auto' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion) ";
			$rqt.= "VALUES (NULL, 'opac', 'websubscribe_num_carte_auto', '', 'Numéro de carte de lecteur automatique ?\n 2,a,b,c: Oui avec préfixe: a=longueur du préfixe, b=nombre de chiffres de la partie numérique, c=préfixe fixé (facultatif)\n 3,fonction: fonction de génération spécifique dans fichier nommé de la même façon, à placer dans pmb/opac_css/circ/empr', 'f_modules', '0')" ;
			echo traite_rqt($rqt,"insert opac_websubscribe_num_carte_auto into parametres") ;
		}	
	case "4":
		//DB - Génération de cartes lecteurs sur imprimante ticket
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pdfcartelecteur' and sstype_param='printer_card_handler' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion) VALUES (0, 'pdfcartelecteur', 'printer_card_handler', '', 'Gestionnaire d\'impression :\n\n 1 = script \"print_cb.php\"\n 2 = applet jzebra\n 3 = requête ajax','',0)";
			echo traite_rqt($rqt,"insert pmb_printer_card_handler into parametres");
		}
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pdfcartelecteur' and sstype_param='printer_card_name' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion) VALUES (0, 'pdfcartelecteur', 'printer_card_name', '', 'Nom de l\'imprimante.','',0)";
			echo traite_rqt($rqt,"insert pmb_printer_card_options into parametres");
		}
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pdfcartelecteur' and sstype_param='printer_card_url' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion) VALUES (0, 'pdfcartelecteur', 'printer_card_url', '', 'Adresse de l\'imprimante.','',0)";
			echo traite_rqt($rqt,"insert pmb_printer_card_url into parametres");
		}
	case "5" :
		//AR - On ajoute une colonne pour l'inscription en ligne à l'OPAC (pour conserver ce que l'on faisait)
		$rqt = "alter table empr add empr_subscription_action text";
		echo traite_rqt($rqt,"alter table empr add empr_subscription_action");
		
		//AR - Modification du paramètre opac_websubscribe_show 
		$rqt = "update parametres set comment_param = 'Afficher la possibilité de s\'inscrire en ligne ?\n0: Non\n1: Oui\n2: Oui + proposition s\'incription sur les réservations/abonnements' where type_param='opac' and sstype_param = 'websubscribe_show'";
		echo traite_rqt($rqt,"update parametres opac_websubscribe_show");		
	case "6" :
		// NG - Transfert: mémorisation de la loc d'origine des exemplaires en transfert
		$rqt = "CREATE TABLE if not exists transferts_source (
			trans_source_numexpl INT UNSIGNED NOT NULL default 0 ,
			trans_source_numloc INT UNSIGNED NOT NULL default 0 ,
			PRIMARY KEY(trans_source_numexpl))";		
		echo traite_rqt($rqt,"CREATE TABLE transferts_source ") ;		
	case "7" :
		// NG - Vignette de la notice		
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='notice_img_folder_id' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion) ";
			$rqt.= "VALUES (NULL, 'pmb', 'notice_img_folder_id', '0', 'Identifiant du répertoire d\'upload des vignettes de notices', '', '0')" ;
			echo traite_rqt($rqt,"insert pmb_notice_img_folder_id into parametres") ;
		}
	case "8" :
		// NG - Ajout dans les archives de prêt les localisations du pret et de la loc d'origine de l'exemplaire
		$rqt = "alter table pret_archive add arc_expl_location_retour INT UNSIGNED NOT NULL default 0 AFTER arc_expl_location";
		echo traite_rqt($rqt,"alter table pret_archive add arc_expl_location_retour");	
		
		$rqt = "alter table pret_archive add arc_expl_location_origine INT UNSIGNED NOT NULL default 0 AFTER arc_expl_location";
		echo traite_rqt($rqt,"alter table pret_archive add arc_expl_location_origine");
	case "9" :
		//DG - Augmentation de la taille du champ pour les équations
		$rqt = "ALTER TABLE equations MODIFY nom_equation TEXT NOT NULL";
		echo traite_rqt($rqt,"ALTER TABLE equations MODIFY nom_equation TEXT");
	case "10" :
		// AP script de vérification de saisie d'un auteur perso
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='autorites_verif_js' "))==0){
			$rqt = "INSERT INTO parametres ( type_param, sstype_param, valeur_param, comment_param,section_param,gestion) 
					VALUES ( 'pmb', 'autorites_verif_js', '', 'Script de vérification de saisie des autorités','', 0)";
			echo traite_rqt($rqt,"insert autorites_verif_js into parametres");
		}
	case "11" :
		//AB paramètre pour masquer/afficher la reservation par panier
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'opac' and sstype_param='resa_cart' "))==0){
			$rqt = "INSERT INTO parametres (type_param,sstype_param,valeur_param,comment_param,section_param,gestion) VALUES ('opac','resa_cart',1,'Paramètre pour masquer/afficher la reservation par panier\n0 : Non \n1 : Oui','a_general',0)";
			echo traite_rqt($rqt,"insert opac_resa_cart into parametres");
		}
	case "12" :
		//DG - En impression de panier, imprimer les exemplaires est coché par défaut
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='print_expl_default' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion) VALUES (0, 'pmb', 'print_expl_default', '0', 'En impression de panier, imprimer les exemplaires est coché par défaut \n 0 : Non \n 1 : Oui','',0) " ;
			echo traite_rqt($rqt,"insert pmb_print_expl_default = 0 into parametres");
		}
	case "13" :
		//DG - Personnalisation des colonnes pour l'affichage des états des collections
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'opac' and sstype_param='collstate_data' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
					VALUES (0, 'opac', 'collstate_data', '', 'Colonne des états des collections, dans l\'ordre donné, séparé par des virgules : location_libelle,emplacement_libelle,cote,type_libelle,statut_opac_libelle,origine,state_collections,archive,lacune,surloc_libelle,note\nLes valeurs possibles sont les propriétés de la classe PHP \"pmb/opac_css/classes/collstate.class.php\".','e_aff_notice',0)";
			echo traite_rqt($rqt,"insert opac_collstate_data = 0 into parametres");
		}
	case "14" :
		// NG - Ajout du champ resa_arc_trans pour associer un transfert à une archive résa
		$rqt = "ALTER TABLE transferts_demande ADD resa_arc_trans int(8) UNSIGNED NOT NULL DEFAULT 0 ";
		echo traite_rqt($rqt,"alter table transferts_demande add resa_arc_trans ");
	case "15" :
		// NG - Ajout champ info dans les audits
		$rqt = "ALTER TABLE audit ADD info text NOT NULL ";
		echo traite_rqt($rqt,"alter table audit add info ");
	case "16" :
		// NG - Ajout param transferts_retour_action_resa
		if (mysql_num_rows(mysql_query("SELECT 1 FROM parametres WHERE type_param= 'transferts' and sstype_param='retour_action_resa' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, gestion, comment_param)
					VALUES (0, 'transferts', 'retour_action_resa', '1', '1', 'Génére un transfert pour répondre à une réservation lors du retour de l\'exemplaire\n 0: Non\n 1: Oui') ";
			echo traite_rqt($rqt,"INSERT transferts_retour_action_resa INTO parametres") ;
		}
	case "17" :
		//DG - Logs OPAC - Exclusion possible des robots et de certaines adresses IP
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='logs_exclude_robots' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
					VALUES (0, 'pmb', 'logs_exclude_robots', '1', 'Exclure les robots dans les logs OPAC ?\n 0: Non\n 1: Oui. \nFaire suivre d\'une virgule pour éventuellement exclure les logs OPAC provenant de certaines adresses IP, elles-mêmes séparées par des virgules (ex : 1,127.0.0.1,192.168.0.1).','',0)";
			echo traite_rqt($rqt,"insert pmb_logs_exclude_robots = 1 into parametres");
		}
	case "18" :
		//MB + CB - Renseigner les champs d'exemplaires transfert_location_origine et transfert_statut_origine  pour les statistiques et si ils ne le sont pas déjà (lié aux améliorations pour les transferts)
		$rqt = "UPDATE exemplaires SET transfert_location_origine=expl_location, transfert_statut_origine=expl_statut, update_date=update_date WHERE transfert_location_origine=0 AND transfert_statut_origine=0 AND expl_id NOT IN (SELECT num_expl FROM transferts_demande JOIN transferts ON (num_transfert=id_transfert AND etat_transfert=0))";
		echo traite_rqt($rqt,"update exemplaires transfert_location_origine transfert_statut_origine");
	case "19" :
		//DG - Modification taille du champ article_resume de la table cms_articles
		$rqt ="alter table cms_articles MODIFY article_resume MEDIUMTEXT NOT NULL";
		echo traite_rqt($rqt,"alter table cms_articles modify article_resume mediumtext");
		//DG - Modification taille du champ article_contenu de la table cms_articles
		$rqt ="alter table cms_articles MODIFY article_contenu MEDIUMTEXT NOT NULL";
		echo traite_rqt($rqt,"alter table cms_articles modify article_contenu mediumtext");
		//DG - Modification taille du champ section_resume de la table cms_sections
		$rqt ="alter table cms_sections MODIFY section_resume MEDIUMTEXT NOT NULL";
		echo traite_rqt($rqt,"alter table cms_sections modify section_resume mediumtext");
	case "20" :
		//MB - Définition de la taille maximum des vignettes des notices 
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='notice_img_pics_max_size' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param) VALUES (0, 'pmb', 'notice_img_pics_max_size', '150', 'Taille maximale des vignettes uploadées dans les notices, en largeur ou en hauteur')";
			echo traite_rqt($rqt,"insert pmb_notice_img_pics_max_size='150' into parametres");
		}
	case '21' :
		//DB - commande psexec (planificateur sous windows)
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='psexec_cmd' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
				VALUES (0, 'pmb', 'psexec_cmd', 'psexec -d', 'Paramètres de lancement de psexec (planificateur sous windows)\r\n\nAjouter l\'option -accepteula sur les versions les plus récentes. ', '',0) ";
			echo traite_rqt($rqt, "insert pmb_psexec_cmd into parameters");
		}
	case '22' :
		// JP - Suggestions - Utilisateur : pouvoir être alerté en cas de nouvelle suggestion à l'OPAC
			$rqt = "ALTER TABLE users ADD user_alert_suggmail int(1) UNSIGNED NOT NULL DEFAULT 0";
			echo traite_rqt($rqt,"alter table users add user_alert_suggmail");
		// JP - Acquisitions - Sélection rubrique budgétaire en commande : pouvoir toutes les afficher
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'acquisition' and sstype_param='budget_show_all' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
					VALUES (0, 'acquisition', 'budget_show_all', '0', 'Sélection d\'une rubrique budgétaire en commande : toutes les afficher ?\n 0: Non (par pagination)\n 1: Oui.','',0)";
			echo traite_rqt($rqt,"insert budget_show_all = 0 into parametres");
		}
	case "23" :
		//MB Ajout index sur le nom des fichiers numériques pour accélérer la recherche
		$add_index=true;
		$req="SHOW INDEX FROM explnum";
		$res=mysql_query($req);
		if($res && mysql_num_rows($res)){
			while ($ligne = mysql_fetch_object($res)){
				if($ligne->Column_name == "explnum_nomfichier"){
					$add_index=false;
					break;
				}
			}
		}
		if($add_index){
			@set_time_limit(0);
			mysql_query("set wait_timeout=28800", $dbh);
			$rqt = "alter table explnum add index i_explnum_nomfichier(explnum_nomfichier(30))";
			echo traite_rqt($rqt,"alter table explnum add index i_explnum_nomfichier");
		}
	case '24' :
		//JP - Ajout deux index sur les liens entre actes pour accélérer la recherche
		$rqt = "alter table liens_actes drop index i_num_acte";
		echo traite_rqt($rqt,"alter table liens_actes drop index i_num_acte");
		$rqt = "alter table liens_actes add index i_num_acte(num_acte)";
		echo traite_rqt($rqt,"alter table liens_actes add index i_num_acte");
		
		$rqt = "alter table liens_actes drop index i_num_acte_lie";
		echo traite_rqt($rqt,"alter table liens_actes drop index i_num_acte_lie");
		$rqt = "alter table liens_actes add index i_num_acte_lie(num_acte_lie)";
		echo traite_rqt($rqt,"alter table liens_actes add index i_num_acte_lie");
	case '25' :
		//JP - Modification taille du champ mailtpl_tpl de la table mailtpl
		$rqt ="alter table mailtpl MODIFY mailtpl_tpl MEDIUMTEXT NOT NULL";
		echo traite_rqt($rqt,"alter table mailtpl modify mailtpl_tpl mediumtext");
	case '26' :
		//JP - Nettoyage des catégories sans libellé
		$rqt ="DELETE FROM categories WHERE libelle_categorie=''";
		echo traite_rqt($rqt,"Delete categories sans libellé");
	case '27' :
		// JP - Abonnements - nom du périodique par défaut en création d'abonnement
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='abt_label_perio' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
					VALUES (0, 'pmb', 'abt_label_perio', '0', 'Création d\'un abonnement : reprendre le nom du périodique ?\n 0: Non \n 1: Oui.','',0)";
			echo traite_rqt($rqt,"insert pmb_abt_label_perio = 0 into parametres");
		}
	case '28' :
		// JP - Acquisitions - afficher le nom de l'abonnement dans les lignes de la commande
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'acquisition' and sstype_param='show_abt_in_cmde' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
				VALUES (0, 'acquisition', 'show_abt_in_cmde', '0', 'Afficher l\'abonnement dans les lignes de la commande ?\n 0: Non \n 1: Oui.','',0)";
			echo traite_rqt($rqt,"insert acquisition_show_abt_in_cmde = 0 into parametres");
		}
	case '29' :
		// MB - Affichage liste des bulletins - Modification explication du paramètre
		$rqt = "UPDATE parametres SET comment_param='Fonction d\'affichage de la liste des bulletins d\'un périodique\nValeurs possibles:\naffichage_liste_bulletins_normale (Si paramètre vide)\naffichage_liste_bulletins_tableau\naffichage_liste_bulletins_depliable' WHERE type_param= 'opac' and sstype_param='fonction_affichage_liste_bull'";
		echo traite_rqt($rqt,"UPDATE parametres opac_fonction_affichage_liste_bull");
	case '30' :
		//JP - Modification de la longueur du champ email de la table coordonnees
		$rqt = "ALTER TABLE coordonnees MODIFY email varchar(255) NOT NULL default '' ";
		echo traite_rqt($rqt,"alter table coordonnees modify email");
	case '31' :
		// MB - LDAP gestion de l'encodage lors de l'import
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'ldap' and sstype_param='encoding_utf8' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
					VALUES (0, 'ldap', 'encoding_utf8', '0', 'Les informations du LDAP sont en utf-8 ?\n 0: Non \n 1: Oui.','',0)";
			echo traite_rqt($rqt,"insert ldap_encoding_utf8 = 0 into parametres");
		}
	case '32' :
		//JP - Renseigner les champs d'exemplaires transfert_location_origine et transfert_statut_origine pour les statistiques et si ils ne le sont pas déjà (ajout sur la requête en v5.17)
		$rqt = "UPDATE exemplaires SET transfert_location_origine=expl_location, update_date=update_date WHERE transfert_location_origine=0 AND expl_id NOT IN (SELECT num_expl FROM transferts_demande JOIN transferts ON (num_transfert=id_transfert AND etat_transfert=0))";
		echo traite_rqt($rqt,"update exemplaires transfert_location_origine");
		
		$rqt = "UPDATE exemplaires SET transfert_statut_origine=expl_statut, update_date=update_date WHERE transfert_statut_origine=0 AND expl_id NOT IN (SELECT num_expl FROM transferts_demande JOIN transferts ON (num_transfert=id_transfert AND etat_transfert=0))";
		echo traite_rqt($rqt,"update exemplaires transfert_statut_origine");
	case '33' :
		//JP - Section origine pour les transferts
		$rqt = "ALTER TABLE exemplaires ADD transfert_section_origine SMALLINT(5) NOT NULL default '0'" ;
		echo traite_rqt($rqt,"ALTER TABLE exemplaires ADD transfert_section_origine ");
	
		$rqt = "UPDATE exemplaires SET transfert_section_origine=expl_section, update_date=update_date WHERE transfert_section_origine=0 AND expl_id NOT IN (SELECT num_expl FROM transferts_demande JOIN transferts ON (num_transfert=id_transfert AND etat_transfert=0))";
		echo traite_rqt($rqt,"update exemplaires transfert_section_origine");
	case '34' :
		//MB: Ajouter une PK aux tables de vue
		$res=mysql_query("SHOW TABLES LIKE 'opac_view_notices_%'");
		if($res && mysql_num_rows($res)){
			while ($r=mysql_fetch_array($res)){
				$rqt = "ALTER TABLE ".$r[0]." DROP INDEX opac_view_num_notice" ;
				echo traite_rqt($rqt,"ALTER TABLE ".$r[0]." DROP INDEX opac_view_num_notice ");
				
				$rqt = "ALTER TABLE ".$r[0]." DROP PRIMARY KEY";
				echo traite_rqt($rqt, "ALTER TABLE ".$r[0]." DROP PRIMARY KEY");
				
				$rqt = "ALTER TABLE ".$r[0]." ADD PRIMARY KEY (opac_view_num_notice)";
				echo traite_rqt($rqt, "ALTER TABLE ".$r[0]." ADD PRIMARY KEY");
			}
		}
	case '35' :
		// JP - Ajout champ demande abonnement sur périodique
		$rqt = "ALTER TABLE notices ADD opac_serialcirc_demande TINYINT UNSIGNED NOT NULL DEFAULT 1";
		echo traite_rqt($rqt,"ALTER TABLE notices ADD opac_serialcirc_demande") ;
	case '36' :
		// JP - Ajout autorisations sur recherches prédéfinies gestion
		$rqt = "ALTER TABLE search_perso ADD autorisations MEDIUMTEXT NULL DEFAULT NULL ";
		echo traite_rqt($rqt,"ALTER TABLE search_perso ADD autorisations") ;
		
		$rqt = "UPDATE search_perso SET autorisations=num_user ";
		echo traite_rqt($rqt,"UPDATE autorisations INTO search_perso");
	case '37' :
		// JP - ajout index manquants sur tables de champs persos
		$rqt = "ALTER TABLE author_custom_values DROP INDEX i_acv_st " ;
		echo traite_rqt($rqt,"DROP INDEX i_acv_st");
		$rqt = "ALTER TABLE author_custom_values ADD INDEX i_acv_st(author_custom_small_text)" ;
		echo traite_rqt($rqt,"ALTER TABLE author_custom_values ADD INDEX i_acv_st");
		
		$rqt = "ALTER TABLE author_custom_values DROP INDEX i_acv_t " ;
		echo traite_rqt($rqt,"DROP INDEX i_acv_t");
		$rqt = "ALTER TABLE author_custom_values ADD INDEX i_acv_t(author_custom_text(255))" ;
		echo traite_rqt($rqt,"ALTER TABLE author_custom_values ADD INDEX i_acv_t");
		
		$rqt = "ALTER TABLE author_custom_values DROP INDEX i_acv_i " ;
		echo traite_rqt($rqt,"DROP INDEX i_acv_i");
		$rqt = "ALTER TABLE author_custom_values ADD INDEX i_acv_i(author_custom_integer)" ;
		echo traite_rqt($rqt,"ALTER TABLE author_custom_values ADD INDEX i_acv_i");
		
		$rqt = "ALTER TABLE author_custom_values DROP INDEX i_acv_d " ;
		echo traite_rqt($rqt,"DROP INDEX i_acv_d");
		$rqt = "ALTER TABLE author_custom_values ADD INDEX i_acv_d(author_custom_date)" ;
		echo traite_rqt($rqt,"ALTER TABLE author_custom_values ADD INDEX i_acv_d");
		
		$rqt = "ALTER TABLE author_custom_values DROP INDEX i_acv_f " ;
		echo traite_rqt($rqt,"DROP INDEX i_acv_f");
		$rqt = "ALTER TABLE author_custom_values ADD INDEX i_acv_f(author_custom_float)" ;
		echo traite_rqt($rqt,"ALTER TABLE author_custom_values ADD INDEX i_acv_f");
		
		$rqt = "ALTER TABLE categ_custom_values DROP INDEX i_ccv_st " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_st");
		$rqt = "ALTER TABLE categ_custom_values ADD INDEX i_ccv_st(categ_custom_small_text)" ;
		echo traite_rqt($rqt,"ALTER TABLE categ_custom_values ADD INDEX i_ccv_st");
		
		$rqt = "ALTER TABLE categ_custom_values DROP INDEX i_ccv_t " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_t");
		$rqt = "ALTER TABLE categ_custom_values ADD INDEX i_ccv_t(categ_custom_text(255))" ;
		echo traite_rqt($rqt,"ALTER TABLE categ_custom_values ADD INDEX i_ccv_t");
		
		$rqt = "ALTER TABLE categ_custom_values DROP INDEX i_ccv_i " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_i");
		$rqt = "ALTER TABLE categ_custom_values ADD INDEX i_ccv_i(categ_custom_integer)" ;
		echo traite_rqt($rqt,"ALTER TABLE categ_custom_values ADD INDEX i_ccv_i");
		
		$rqt = "ALTER TABLE categ_custom_values DROP INDEX i_ccv_d " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_d");
		$rqt = "ALTER TABLE categ_custom_values ADD INDEX i_ccv_d(categ_custom_date)" ;
		echo traite_rqt($rqt,"ALTER TABLE categ_custom_values ADD INDEX i_ccv_d");
		
		$rqt = "ALTER TABLE categ_custom_values DROP INDEX i_ccv_f " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_f");
		$rqt = "ALTER TABLE categ_custom_values ADD INDEX i_ccv_f(categ_custom_float)" ;
		echo traite_rqt($rqt,"ALTER TABLE categ_custom_values ADD INDEX i_ccv_f");
		
		$rqt = "ALTER TABLE cms_editorial_custom_values DROP INDEX i_ccv_st " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_st");
		$rqt = "ALTER TABLE cms_editorial_custom_values ADD INDEX i_ccv_st(cms_editorial_custom_small_text)" ;
		echo traite_rqt($rqt,"ALTER TABLE cms_editorial_custom_values ADD INDEX i_ccv_st");
		
		$rqt = "ALTER TABLE cms_editorial_custom_values DROP INDEX i_ccv_t " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_t");
		$rqt = "ALTER TABLE cms_editorial_custom_values ADD INDEX i_ccv_t(cms_editorial_custom_text(255))" ;
		echo traite_rqt($rqt,"ALTER TABLE cms_editorial_custom_values ADD INDEX i_ccv_t");
		
		$rqt = "ALTER TABLE cms_editorial_custom_values DROP INDEX i_ccv_i " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_i");
		$rqt = "ALTER TABLE cms_editorial_custom_values ADD INDEX i_ccv_i(cms_editorial_custom_integer)" ;
		echo traite_rqt($rqt,"ALTER TABLE cms_editorial_custom_values ADD INDEX i_ccv_i");
		
		$rqt = "ALTER TABLE cms_editorial_custom_values DROP INDEX i_ccv_d " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_d");
		$rqt = "ALTER TABLE cms_editorial_custom_values ADD INDEX i_ccv_d(cms_editorial_custom_date)" ;
		echo traite_rqt($rqt,"ALTER TABLE cms_editorial_custom_values ADD INDEX i_ccv_d");
		
		$rqt = "ALTER TABLE cms_editorial_custom_values DROP INDEX i_ccv_f " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_f");
		$rqt = "ALTER TABLE cms_editorial_custom_values ADD INDEX i_ccv_f(cms_editorial_custom_float)" ;
		echo traite_rqt($rqt,"ALTER TABLE cms_editorial_custom_values ADD INDEX i_ccv_f");
		
		$rqt = "ALTER TABLE collection_custom_values DROP INDEX i_ccv_st " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_st");
		$rqt = "ALTER TABLE collection_custom_values ADD INDEX i_ccv_st(collection_custom_small_text)" ;
		echo traite_rqt($rqt,"ALTER TABLE collection_custom_values ADD INDEX i_ccv_st");
		
		$rqt = "ALTER TABLE collection_custom_values DROP INDEX i_ccv_t " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_t");
		$rqt = "ALTER TABLE collection_custom_values ADD INDEX i_ccv_t(collection_custom_text(255))" ;
		echo traite_rqt($rqt,"ALTER TABLE collection_custom_values ADD INDEX i_ccv_t");
		
		$rqt = "ALTER TABLE collection_custom_values DROP INDEX i_ccv_i " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_i");
		$rqt = "ALTER TABLE collection_custom_values ADD INDEX i_ccv_i(collection_custom_integer)" ;
		echo traite_rqt($rqt,"ALTER TABLE collection_custom_values ADD INDEX i_ccv_i");
		
		$rqt = "ALTER TABLE collection_custom_values DROP INDEX i_ccv_d " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_d");
		$rqt = "ALTER TABLE collection_custom_values ADD INDEX i_ccv_d(collection_custom_date)" ;
		echo traite_rqt($rqt,"ALTER TABLE collection_custom_values ADD INDEX i_ccv_d");
		
		$rqt = "ALTER TABLE collection_custom_values DROP INDEX i_ccv_f " ;
		echo traite_rqt($rqt,"DROP INDEX i_ccv_f");
		$rqt = "ALTER TABLE collection_custom_values ADD INDEX i_ccv_f(collection_custom_float)" ;
		echo traite_rqt($rqt,"ALTER TABLE collection_custom_values ADD INDEX i_ccv_f");
		
		$rqt = "ALTER TABLE gestfic0_custom_values DROP INDEX i_gcv_st " ;
		echo traite_rqt($rqt,"DROP INDEX i_gcv_st");
		$rqt = "ALTER TABLE gestfic0_custom_values ADD INDEX i_gcv_st(gestfic0_custom_small_text)" ;
		echo traite_rqt($rqt,"ALTER TABLE gestfic0_custom_values ADD INDEX i_gcv_st");
		
		$rqt = "ALTER TABLE gestfic0_custom_values DROP INDEX i_gcv_t " ;
		echo traite_rqt($rqt,"DROP INDEX i_gcv_t");
		$rqt = "ALTER TABLE gestfic0_custom_values ADD INDEX i_gcv_t(gestfic0_custom_text(255))" ;
		echo traite_rqt($rqt,"ALTER TABLE gestfic0_custom_values ADD INDEX i_gcv_t");
		
		$rqt = "ALTER TABLE gestfic0_custom_values DROP INDEX i_gcv_i " ;
		echo traite_rqt($rqt,"DROP INDEX i_gcv_i");
		$rqt = "ALTER TABLE gestfic0_custom_values ADD INDEX i_gcv_i(gestfic0_custom_integer)" ;
		echo traite_rqt($rqt,"ALTER TABLE gestfic0_custom_values ADD INDEX i_gcv_i");
		
		$rqt = "ALTER TABLE gestfic0_custom_values DROP INDEX i_gcv_d " ;
		echo traite_rqt($rqt,"DROP INDEX i_gcv_d");
		$rqt = "ALTER TABLE gestfic0_custom_values ADD INDEX i_gcv_d(gestfic0_custom_date)" ;
		echo traite_rqt($rqt,"ALTER TABLE gestfic0_custom_values ADD INDEX i_gcv_d");
		
		$rqt = "ALTER TABLE gestfic0_custom_values DROP INDEX i_gcv_f " ;
		echo traite_rqt($rqt,"DROP INDEX i_gcv_f");
		$rqt = "ALTER TABLE gestfic0_custom_values ADD INDEX i_gcv_f(gestfic0_custom_float)" ;
		echo traite_rqt($rqt,"ALTER TABLE gestfic0_custom_values ADD INDEX i_gcv_f");
		
		$rqt = "ALTER TABLE indexint_custom_values DROP INDEX i_icv_st " ;
		echo traite_rqt($rqt,"DROP INDEX i_icv_st");
		$rqt = "ALTER TABLE indexint_custom_values ADD INDEX i_icv_st(indexint_custom_small_text)" ;
		echo traite_rqt($rqt,"ALTER TABLE indexint_custom_values ADD INDEX i_icv_st");
		
		$rqt = "ALTER TABLE indexint_custom_values DROP INDEX i_icv_t " ;
		echo traite_rqt($rqt,"DROP INDEX i_icv_t");
		$rqt = "ALTER TABLE indexint_custom_values ADD INDEX i_icv_t(indexint_custom_text(255))" ;
		echo traite_rqt($rqt,"ALTER TABLE indexint_custom_values ADD INDEX i_icv_t");
		
		$rqt = "ALTER TABLE indexint_custom_values DROP INDEX i_icv_i " ;
		echo traite_rqt($rqt,"DROP INDEX i_icv_i");
		$rqt = "ALTER TABLE indexint_custom_values ADD INDEX i_icv_i(indexint_custom_integer)" ;
		echo traite_rqt($rqt,"ALTER TABLE indexint_custom_values ADD INDEX i_icv_i");
		
		$rqt = "ALTER TABLE indexint_custom_values DROP INDEX i_icv_d " ;
		echo traite_rqt($rqt,"DROP INDEX i_icv_d");
		$rqt = "ALTER TABLE indexint_custom_values ADD INDEX i_icv_d(indexint_custom_date)" ;
		echo traite_rqt($rqt,"ALTER TABLE indexint_custom_values ADD INDEX i_icv_d");
		
		$rqt = "ALTER TABLE indexint_custom_values DROP INDEX i_icv_f " ;
		echo traite_rqt($rqt,"DROP INDEX i_icv_f");
		$rqt = "ALTER TABLE indexint_custom_values ADD INDEX i_icv_f(indexint_custom_float)" ;
		echo traite_rqt($rqt,"ALTER TABLE indexint_custom_values ADD INDEX i_icv_f");
		
		$rqt = "ALTER TABLE publisher_custom_values DROP INDEX i_pcv_st " ;
		echo traite_rqt($rqt,"DROP INDEX i_pcv_st");
		$rqt = "ALTER TABLE publisher_custom_values ADD INDEX i_pcv_st(publisher_custom_small_text)" ;
		echo traite_rqt($rqt,"ALTER TABLE publisher_custom_values ADD INDEX i_pcv_st");
		
		$rqt = "ALTER TABLE publisher_custom_values DROP INDEX i_pcv_t " ;
		echo traite_rqt($rqt,"DROP INDEX i_pcv_t");
		$rqt = "ALTER TABLE publisher_custom_values ADD INDEX i_pcv_t(publisher_custom_text(255))" ;
		echo traite_rqt($rqt,"ALTER TABLE publisher_custom_values ADD INDEX i_pcv_t");
		
		$rqt = "ALTER TABLE publisher_custom_values DROP INDEX i_pcv_i " ;
		echo traite_rqt($rqt,"DROP INDEX i_pcv_i");
		$rqt = "ALTER TABLE publisher_custom_values ADD INDEX i_pcv_i(publisher_custom_integer)" ;
		echo traite_rqt($rqt,"ALTER TABLE publisher_custom_values ADD INDEX i_pcv_i");
		
		$rqt = "ALTER TABLE publisher_custom_values DROP INDEX i_pcv_d " ;
		echo traite_rqt($rqt,"DROP INDEX i_pcv_d");
		$rqt = "ALTER TABLE publisher_custom_values ADD INDEX i_pcv_d(publisher_custom_date)" ;
		echo traite_rqt($rqt,"ALTER TABLE publisher_custom_values ADD INDEX i_pcv_d");
		
		$rqt = "ALTER TABLE publisher_custom_values DROP INDEX i_pcv_f " ;
		echo traite_rqt($rqt,"DROP INDEX i_pcv_f");
		$rqt = "ALTER TABLE publisher_custom_values ADD INDEX i_pcv_f(publisher_custom_float)" ;
		echo traite_rqt($rqt,"ALTER TABLE publisher_custom_values ADD INDEX i_pcv_f");
		
		$rqt = "ALTER TABLE serie_custom_values DROP INDEX i_scv_st " ;
		echo traite_rqt($rqt,"DROP INDEX i_scv_st");
		$rqt = "ALTER TABLE serie_custom_values ADD INDEX i_scv_st(serie_custom_small_text)" ;
		echo traite_rqt($rqt,"ALTER TABLE serie_custom_values ADD INDEX i_scv_st");
		
		$rqt = "ALTER TABLE serie_custom_values DROP INDEX i_scv_t " ;
		echo traite_rqt($rqt,"DROP INDEX i_scv_t");
		$rqt = "ALTER TABLE serie_custom_values ADD INDEX i_scv_t(serie_custom_text(255))" ;
		echo traite_rqt($rqt,"ALTER TABLE serie_custom_values ADD INDEX i_scv_t");
		
		$rqt = "ALTER TABLE serie_custom_values DROP INDEX i_scv_i " ;
		echo traite_rqt($rqt,"DROP INDEX i_scv_i");
		$rqt = "ALTER TABLE serie_custom_values ADD INDEX i_scv_i(serie_custom_integer)" ;
		echo traite_rqt($rqt,"ALTER TABLE serie_custom_values ADD INDEX i_scv_i");
		
		$rqt = "ALTER TABLE serie_custom_values DROP INDEX i_scv_d " ;
		echo traite_rqt($rqt,"DROP INDEX i_scv_d");
		$rqt = "ALTER TABLE serie_custom_values ADD INDEX i_scv_d(serie_custom_date)" ;
		echo traite_rqt($rqt,"ALTER TABLE serie_custom_values ADD INDEX i_scv_d");
		
		$rqt = "ALTER TABLE serie_custom_values DROP INDEX i_scv_f " ;
		echo traite_rqt($rqt,"DROP INDEX i_scv_f");
		$rqt = "ALTER TABLE serie_custom_values ADD INDEX i_scv_f(serie_custom_float)" ;
		echo traite_rqt($rqt,"ALTER TABLE serie_custom_values ADD INDEX i_scv_f");
		
		$rqt = "ALTER TABLE subcollection_custom_values DROP INDEX i_scv_st " ;
		echo traite_rqt($rqt,"DROP INDEX i_scv_st");
		$rqt = "ALTER TABLE subcollection_custom_values ADD INDEX i_scv_st(subcollection_custom_small_text)" ;
		echo traite_rqt($rqt,"ALTER TABLE subcollection_custom_values ADD INDEX i_scv_st");
		
		$rqt = "ALTER TABLE subcollection_custom_values DROP INDEX i_scv_t " ;
		echo traite_rqt($rqt,"DROP INDEX i_scv_t");
		$rqt = "ALTER TABLE subcollection_custom_values ADD INDEX i_scv_t(subcollection_custom_text(255))" ;
		echo traite_rqt($rqt,"ALTER TABLE subcollection_custom_values ADD INDEX i_scv_t");
		
		$rqt = "ALTER TABLE subcollection_custom_values DROP INDEX i_scv_i " ;
		echo traite_rqt($rqt,"DROP INDEX i_scv_i");
		$rqt = "ALTER TABLE subcollection_custom_values ADD INDEX i_scv_i(subcollection_custom_integer)" ;
		echo traite_rqt($rqt,"ALTER TABLE subcollection_custom_values ADD INDEX i_scv_i");
		
		$rqt = "ALTER TABLE subcollection_custom_values DROP INDEX i_scv_d " ;
		echo traite_rqt($rqt,"DROP INDEX i_scv_d");
		$rqt = "ALTER TABLE subcollection_custom_values ADD INDEX i_scv_d(subcollection_custom_date)" ;
		echo traite_rqt($rqt,"ALTER TABLE subcollection_custom_values ADD INDEX i_scv_d");
		
		$rqt = "ALTER TABLE subcollection_custom_values DROP INDEX i_scv_f " ;
		echo traite_rqt($rqt,"DROP INDEX i_scv_f");
		$rqt = "ALTER TABLE subcollection_custom_values ADD INDEX i_scv_f(subcollection_custom_float)" ;
		echo traite_rqt($rqt,"ALTER TABLE subcollection_custom_values ADD INDEX i_scv_f");
		
		$rqt = "ALTER TABLE tu_custom_values DROP INDEX i_tcv_st " ;
		echo traite_rqt($rqt,"DROP INDEX i_tcv_st");
		$rqt = "ALTER TABLE tu_custom_values ADD INDEX i_tcv_st(tu_custom_small_text)" ;
		echo traite_rqt($rqt,"ALTER TABLE tu_custom_values ADD INDEX i_tcv_st");
		
		$rqt = "ALTER TABLE tu_custom_values DROP INDEX i_tcv_t " ;
		echo traite_rqt($rqt,"DROP INDEX i_tcv_t");
		$rqt = "ALTER TABLE tu_custom_values ADD INDEX i_tcv_t(tu_custom_text(255))" ;
		echo traite_rqt($rqt,"ALTER TABLE tu_custom_values ADD INDEX i_tcv_t");
		
		$rqt = "ALTER TABLE tu_custom_values DROP INDEX i_tcv_i " ;
		echo traite_rqt($rqt,"DROP INDEX i_tcv_i");
		$rqt = "ALTER TABLE tu_custom_values ADD INDEX i_tcv_i(tu_custom_integer)" ;
		echo traite_rqt($rqt,"ALTER TABLE tu_custom_values ADD INDEX i_tcv_i");
		
		$rqt = "ALTER TABLE tu_custom_values DROP INDEX i_tcv_d " ;
		echo traite_rqt($rqt,"DROP INDEX i_tcv_d");
		$rqt = "ALTER TABLE tu_custom_values ADD INDEX i_tcv_d(tu_custom_date)" ;
		echo traite_rqt($rqt,"ALTER TABLE tu_custom_values ADD INDEX i_tcv_d");
		
		$rqt = "ALTER TABLE tu_custom_values DROP INDEX i_tcv_f " ;
		echo traite_rqt($rqt,"DROP INDEX i_tcv_f");
		$rqt = "ALTER TABLE tu_custom_values ADD INDEX i_tcv_f(tu_custom_float)" ;
		echo traite_rqt($rqt,"ALTER TABLE tu_custom_values ADD INDEX i_tcv_f");
		
	case '38' :
		//JP - Nettoyage vues en erreur suite ajout index unique
		$res=mysql_query("SHOW TABLES LIKE 'opac_view_notices_%'");
		if($res && mysql_num_rows($res)){
			while ($r=mysql_fetch_array($res)){
				$rqt = "TRUNCATE TABLE ".$r[0] ;
				echo traite_rqt($rqt,"TRUNCATE TABLE ".$r[0]);
				
				$rqt = "ALTER TABLE ".$r[0]." DROP INDEX opac_view_num_notice" ;
				echo traite_rqt($rqt,"ALTER TABLE ".$r[0]." DROP INDEX opac_view_num_notice ");
				
				$rqt = "ALTER TABLE ".$r[0]." DROP PRIMARY KEY";
				echo traite_rqt($rqt, "ALTER TABLE ".$r[0]." DROP PRIMARY KEY");
				
				$rqt = "ALTER TABLE ".$r[0]." ADD PRIMARY KEY (opac_view_num_notice)";
				echo traite_rqt($rqt, "ALTER TABLE ".$r[0]." ADD PRIMARY KEY");
			}
			
			$rqt = " select 1 " ;
			echo traite_rqt($rqt,"<b><a href='".$base_path."/admin.php?categ=opac&sub=opac_view&section=list' target=_blank>VOUS DEVEZ RECALCULER LES VUES OPAC (APRES ETAPES DE MISE A JOUR) / YOU MUST RECALCULATE OPAC VIEWS (STEPS AFTER UPDATE) : Admin > Vues Opac > Générer les recherches</a></b> ") ;
		}
	case '39' :
		//JP - nettoyage table authorities_sources
		$rqt = "DELETE FROM authorities_sources WHERE num_authority=0";
		echo traite_rqt($rqt,"DELETE FROM authorities_sources num_authority vide");
	case '40' :
		//JP - modification index notices_mots_global_index
			$rqt = "truncate table notices_mots_global_index";
			echo traite_rqt($rqt,"truncate table notices_mots_global_index");
			$rqt ="alter table notices_mots_global_index drop primary key";
			echo traite_rqt($rqt,"alter table notices_mots_global_index drop primary key");
			$rqt ="alter table notices_mots_global_index add primary key (id_notice,code_champ,code_ss_champ,num_word,position,field_position)";
			echo traite_rqt($rqt,"alter table notices_mots_global_index add primary key");
			// Info de réindexation
			$rqt = " select 1 " ;
			echo traite_rqt($rqt,"<b><a href='".$base_path."/admin.php?categ=netbase' target=_blank>VOUS DEVEZ REINDEXER (APRES ETAPES DE MISE A JOUR) / YOU MUST REINDEX (STEPS AFTER UPDATE) : Admin > Outils > Nettoyage de base</a></b> ") ;
	case '41' :
		//JP - recalcul des isbn à cause du nouveau fomatage
		require_once($include_path."/isbn.inc.php");
		$res=mysql_query("SELECT notice_id, code FROM notices WHERE code<>'' AND niveau_biblio='m' AND code LIKE '97%'");
		if($res && mysql_num_rows($res)){
			while ($row=mysql_fetch_object($res)) {
				$code = $row->code;
				$new_code = formatISBN($code);
				if ($code!= $new_code){
					mysql_query("UPDATE notices SET code='".addslashes($new_code)."', update_date=update_date WHERE notice_id=".$row->notice_id);
				}
			}
		}
		$rqt = " select 1 " ;
		echo traite_rqt($rqt,"update notices code / ISBN check and clean") ;
	case '42' :
		//JP - taille de certains champs blob trop juste
		$rqt = "ALTER TABLE opac_sessions CHANGE session session MEDIUMBLOB NULL DEFAULT NULL";
		echo traite_rqt($rqt,"ALTER TABLE opac_sessions CHANGE session MEDIUMBLOB");
		$rqt = " select 1 " ;
		echo traite_rqt($rqt,"<b><a href='".$base_path."/admin.php?categ=netbase' target=_blank>VOUS DEVEZ FAIRE UN NETOYAGE DE BASE (APRES ETAPES DE MISE A JOUR) / YOU MUST DO A DATABASE CLEANUP (STEPS AFTER UPDATE) : Admin > Outils > Nettoyage de base</a></b> ") ;
	case '43' :
		// JP - paramètre mail_adresse_from pour l'envoi de mails
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='mail_adresse_from' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
					VALUES (0, 'pmb', 'mail_adresse_from', '', 'Adresse d\'expédition des emails. Ce paramètre permet de forcer le From des mails envoyés par PMB. Le reply-to reste inchangé (mail de l\'utilisateur en DSI ou relance, mail de la localisation ou paramètre opac_biblio_mail à défaut).\nFormat : adresse_email;libellé\nExemple : pmb@sigb.net;PMB Services' ,'',0)";
			echo traite_rqt($rqt,"insert pmb_mail_adresse_from into parametres");
		}
		if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'opac' and sstype_param='mail_adresse_from' "))==0){
			$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
					VALUES (0, 'opac', 'mail_adresse_from', '', 'Adresse d\'expédition des emails. Ce paramètre permet de forcer le From des mails envoyés par PMB. Le reply-to reste inchangé (mail de l\'utilisateur en DSI ou relance, mail de la localisation ou paramètre opac_biblio_mail à défaut).\nFormat : adresse_email;libellé\nExemple : pmb@sigb.net;PMB Services' ,'a_general',0)";
			echo traite_rqt($rqt,"insert opac_mail_adresse_from into parametres");
		}
		
}		

/******************** JUSQU'ICI **************************************************/
/* PENSER à faire +1 au paramètre $pmb_subversion_database_as_it_shouldbe dans includes/config.inc.php */
/* COMMITER les deux fichiers addon.inc.php ET config.inc.php en même temps */

echo traite_rqt("update parametres set valeur_param='".$pmb_subversion_database_as_it_shouldbe."' where type_param='pmb' and sstype_param='bdd_subversion'","Update to $pmb_subversion_database_as_it_shouldbe database subversion.");
echo "<table>";