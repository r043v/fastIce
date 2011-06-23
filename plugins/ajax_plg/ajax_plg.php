<?php	addToJs('function ajaxPluginCall(plg,args,callback){$.post("'.site_url.module_path.'/ajaxplg/ajax.php?plg="+encodeURIComponent(plg), args, callback);}');
	function fn_ajax_plg($args){}
?>