<?php
	session_start();
	
	include 'fastIce.php';

	function live($url='fr/index')
	{	
		$path = explode('/',$url);
		if(strlen($path[0]) == 2) // check for lang
			$lang = array_shift($path);
		else	$lang = defaultLangage;

		if(isset($path[1])) // there is a path ?
		{	$pageToShow = array_pop($path);
			$urlPath = array_shift($path); foreach($path as $word) $urlPath .= '/'.$word;
		} else
		{	$urlPath = '';
			if(isset($path[0]))
				$pageToShow = $path[0];
			else	$pageToShow = 'index';
		}

		$page = renderPage($pageToShow,$lang,$urlPath,function($page)
		{	callPlugin('page',array('info'));
		},'live',true);

		return $page;
	}
	
	$url = $_POST['url'];
	
	print live($url);
	session_write_close();
?>