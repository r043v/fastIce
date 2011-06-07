<?php /* fastIce global config */

// default page langage and php localisation
define('defaultLangage','fr');
setlocale (LC_ALL, 'fr_FR.utf8','fra');

// folder who will contain html template files
define ('template','mytemplate');

// redis server address and dedicated key prefix
define('redisServer','127.0.0.1');
define('redisPrefix','your-site');

// define website entry point, set to '/' if you put this file at the root, else set it to '/subfolder/'
define('site_url','/');

// http address to the entry point site path, set to 'http://your-site.com/' if you put this file at the root, else set it to 'http://your-site.com/subfolder/'
define('site_full_url','http://mysite.com/');

// mail domain, in case of send email plugin added who will read this address, generally set it as your-site.com
define('mail_domain','mysite.com');

// default info for meta and page title
define('defaultTitle','');
define('defaultKeywords','');
define('defaultDescription','');
define('defaultMeta','');

// some various folder and files names
define ('common_path','common'); // common folder for template engine
define ('module_path','plugins'); // plugin folder
define ('design_path','constants.ini'); // constant design / page part files name (in common folder and dedicated page folder)

?>