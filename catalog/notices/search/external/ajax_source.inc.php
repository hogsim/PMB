<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: ajax_source.inc.php,v 1.1.4.3 2015-05-20 09:43:41 jpermanne Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

mysql_query("delete from source_sync where source_id=".$item);
$result = array(
	'source_id'=>$item
);
ajax_http_send_response($result);