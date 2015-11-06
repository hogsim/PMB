<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: input_epires.inc.php,v 1.6.14.1 2015-09-22 13:22:27 mbertin Exp $

function _get_n_notices_($fi,$file_in,$input_params,$origine) {
	global $base_path,$charset;
	//mysql_query("delete from import_marc");
	
	$first=true;
	$stop=false;
	$content="";
	$index=array();
	$n=1;
	//Lecture du fichier d'entr�e
	while (!$stop) {
		
		//Recherche de +++
		$pos_deb=strpos($content,"+++");
		while (($pos_deb===false)&&(!feof($fi))) {
			$tmp_content=fread($fi,4096);
			if($_SESSION["encodage_fic_source"]){//On a forc� l'encodage
				if(($charset == "utf-8") && ($_SESSION["encodage_fic_source"] == "iso8859")){
					$tmp_content=utf8_encode($tmp_content);
				}elseif(($charset == "iso-8859-1" && ($_SESSION["encodage_fic_source"] == "utf8"))){
					$tmp_content=utf8_decode($tmp_content);
				}
			}
			$content.=$tmp_content;
			$content=str_replace("!\r\n ","",$content);
			$content=str_replace("!\r ","",$content);
			$content=str_replace("!\n ","",$content);
			$pos_deb=strpos($content,"+++");
		}
		
		//D�but accroch�
		if ($pos_deb!==false) {
			//Notice = d�but jusqu'au +++
			$notice=substr($content,0,$pos_deb);
			$content=substr($content,$pos_deb+3);
		} else {
			//Pas de notice suivante, c'est la fin du fichier
			$notice=$content;
			$stop=true;
		}
		
		//Si c'est la premi�re notice, c'est la ligne d'intitul�s !!
		if ($first) {
			$cols=explode(";;",$notice);
			$infos["COLS"]=$cols;
			$filename=explode("/",$file_in);
			$filename=explode(".",$filename[count($filename)-1]);
			//Supression du num�ro origine
			$filename=str_replace($origine,"",$filename);
			$infos["FILENAME"]=$filename[0];
			$fcols=fopen("$base_path/temp/".$origine."_cols.txt","w+");
			if ($fcols) {
				fwrite($fcols,serialize($infos));
				fclose($fcols);
			}
			$notice="";
			$first=false;
		} 
		if ($notice) {
			$requete="INSERT INTO import_marc (no_notice, notice, origine) VALUES ($n,'".addslashes($notice)."','$origine')";
			mysql_query($requete);
			$n++;
			$t=array();
			$t["POS"]=$n;
			$t["LENGHT"]=1;
			$index[]=$t;
		}
	}
	return $index;
}


?>