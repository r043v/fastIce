<?php /* fastIce framework global config */

// default page langage and php localisation
define('defaultLangage','fr');
setlocale (LC_TIME, 'fr_FR.utf8'); // use "locale -a" to get locate list of your system

// folder who will contain html template files
define ('template','template');

// redis server address and dedicated key prefix
define('redisServer','127.0.0.1');
define('redisPrefix','myprefix');

// define website entry point, set to '/' if you put this file at the root, else set it to '/subfolder/'
define('site_url','/');

define('domain_name','domain.net'); // without http !

// mail domain, in case of send email plugin added who will read this address, default is your site domain
define('mail_domain',domain_name);

// the default skeleton name, must be a .html file with the same name in *template*/*common_path*/skeleton/ folder
define('defaultSkeletonName','normal');

// declare jquery location, you can use [url] keyword in case of a local storage
define('jqueryLocation','[url]js/jquery.min.js');//'http://code.jquery.com/jquery-latest.min.js');

// the default page skeleton if the page dedicated one is not found, here is minimal html5 one.
define('defaultSkeleton','<!DOCTYPE html>
<html lang="[lang]">
	<head>	<meta charset="UTF-8" />
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
define ('module_path','plugins'); // plugin dedicated folder
define ('admin_path','admins'); // admin dedicated folder
define ('design_path','constants.ini'); // constants filename

// gz out compression enable or not
define ('enable_gz_compression',true);

// gz compression output for dynamic pages : 0 disable, else is compression power
define ('gz_compression',1);

// cache no charge validity (in second)
define ('cacheTTL',10);

?>