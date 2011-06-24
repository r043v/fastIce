<?php /* fastIce framework global config */

// default page langage and php localisation
define('defaultLangage','fr');
setlocale (LC_ALL, 'fr_FR.utf8','fra');

// folder who will contain html template files
define ('template','template');

// redis server address and dedicated key prefix
define('redisServer','127.0.0.1');
define('redisPrefix','mysite');

// define website entry point, set to '/' if you put this file at the root, else set it to '/subfolder/'
define('site_url','/mysite/');

define('domain_name','mysite.com'); // without http !

// mail domain, in case of send email plugin added who will read this address, default is your site domain
define('mail_domain',domain_name);

// the default page skeleton, here is minimal html5 one with jquery loading.
define('defaultSkeleton','<!DOCTYPE html>
<html lang="[lang]">
	<head>	<meta charset="UTF-8" />
		<script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
		[head]
		§head§
	</head>
	<body>	[body]
		§body§
	</body>
</html>');

// default info for meta and page title
define('defaultTitle','hello i\'m the title !');
define('defaultKeywords','key,words');
define('defaultDescription','a nice description :)');
define('defaultMeta','');

// some various folder and files names
define ('common_path','common'); // common folder for template engine
define ('module_path','plugins'); // plugin folder
define ('design_path','constants.ini'); // constants filename

// gz compression output for dynamic pages : 0 disable, else is compression power
define ('gz_compression',1);

?>