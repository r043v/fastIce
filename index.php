<?php

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

$page = renderPage($pageToShow,$lang,$urlPath);

session_write_close();

exit($page);

?>