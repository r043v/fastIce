<?php	session_start();
	include '../../fastIce.php';
	$out = callPlugin($_GET['plg'],array_values($_POST));
	if($out === false) print 'error!'; else echo $out;
?>