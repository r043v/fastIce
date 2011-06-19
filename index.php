<?php

$tstart = microtime(true);
session_start();

include 'fastIce.php';

if(isset($_GET['p']))
	$pageToShow = $_GET['p'];
else	$pageToShow = 'index';

if(isset($_GET['path']))
	$urlPath = $_GET['path'];
else	$urlPath = '';

if(isset($_GET['lang']))
	setInfo($_GET['lang'],$urlPath);
else	setInfo(defaultLangage,$urlPath);

$page = renderPage($pageToShow,function()
{	

});

session_write_close();

$page = str_replace('[time]','generated in '.round(((microtime(true)-$tstart)*1000),3).' ms.',$page);

//echo $page;
exit($page);

?>