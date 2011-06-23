<?php

//$tstart = microtime(true);
session_start();

include 'fastIce.php';

if(isset($_GET['p']))
	$pageToShow = $_GET['p'];
else	$pageToShow = 'index';

if(isset($_GET['path']))
	$urlPath = $_GET['path'];
else	$urlPath = '';

if(isset($_GET['lang']))
	$lang = $_GET['lang'];
else	$lang = defaultLangage;

$page = renderPage($pageToShow,$lang,$urlPath,function()
{

});

session_write_close();
//print 'generating time : '.round((microtime(true)-$tstart)*1000,3).'<br>';

exit($page);

?>