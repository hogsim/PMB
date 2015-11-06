<?php
// +-------------------------------------------------+
// | 2002-2011 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: printer.class.php,v 1.2.2.1 2014-05-12 15:24:21 dbellamy Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

require_once($class_path."/printer/printer_data.class.php");
require_once($class_path."/printer/printer_data_converter.class.php");

class printer {
	
	public $printer_name='metapace';			// nom de l'imprimante
	public $printer_driver='metapace';			// driver imprimante
	public $printer_data=NULL;					// info d'impression
	public $printer_data_convert_to='';			// conversion des donn�es
	public $printer_jzebra=true;
	public $printer_jzebra_url = '';

	public function __construct(){
	}
	
	public function initialize() {
		
		global $class_path, $pmb_opac_url;
		require_once($class_path.'/printer/'.$this->printer_driver.'.class.php');
		if(!$this->printer_jzebra_url && $pmb_opac_url) {
			$this->printer_jzebra_url = $pmb_opac_url."includes/javascript/printers/zebra/jzebra.jar";
		}
		$this->printer_driver=new $this->printer_driver();
		$this->printer_data= new printer_data();
	}
	
	public function get_script() {
		$script = '';
		if ($this->printer_jzebra) {
//			$script = "<applet name='jzebra' code='jzebra.PrintApplet.class' archive= '".$this->printer_jzebra_url."' width='0px' height='0px'><param name='printer' value='".$this->printer_name."'></applet>";
			$script = "<applet name='jzebra' code='jzebra.PrintApplet.class' archive= '".$this->printer_jzebra_url."' width='0px' height='0px'></applet>";
		}
		return $script;		
	}
	
	protected function fetch_data(){

	}
	
	private function gen_print($data,$tpl_perso='') {
		
		$r='';
		if($this->printer_data_convert_to) {
			$data = printer_data_converter::convert_to($data,$this->printer_data_convert_to);
		}
		$r = $this->printer_driver->gen_print($data,$tpl_perso);
		return $r;
		
	}
	
	public function print_pret($id_empr,$cb_doc,$tpl_perso=''){
		$this->printer_data->get_data_empr($id_empr);
		$this->printer_data->get_data_expl($cb_doc);
		$r = $this->gen_print($this->printer_data->data,$tpl_perso);
	}

	public function print_all_pret($id_empr,$tpl_perso=''){
		global $dbh;
		$this->printer_data->get_data_empr($id_empr);
		$query = "select expl_cb from pret,exemplaires  where pret_idempr=$id_empr and expl_id=pret_idexpl ";		
		$result = mysql_query($query, $dbh);		
		while (($r= mysql_fetch_object($result))) {	
			$this->printer_data->get_data_expl($r->expl_cb,$tpl_perso);		
		}
		
		$query = "select * from resa where resa.resa_idempr=$id_empr ";
		$result = mysql_query($query, $dbh);
		while($resa = mysql_fetch_object($result)) {
			$this->printer_data->get_data_resa($resa->id_resa);	
		}
		$r = $this->gen_print($this->printer_data->data,$tpl_perso);
		return $r;
	}
	
	public function transacash_ticket($transacash_id,$tpl_perso=''){
		global $dbh;
		$this->printer_data->get_data_empr($id_empr);
		$this->gen_print($this->printer_data->data,$tpl_perso);
		$r = $this->gen_print($this->printer_data->data,$tpl_perso);
		return $r;
	}
	
	public function print_card($id_empr,$tpl_perso=''){
		$this->printer_data->get_data_empr($id_empr);
		$r = $this->gen_print($this->printer_data->data,$tpl_perso);
		return $r;
		
	
	}
	
	
}