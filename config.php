<?php // fastIce global config

define ('template','template');

define('redisServer','127.0.0.1');
define('redisPrefix','laphrodite');

define('site_url','/laphrodite/');
define('site_full_url','http://rov.hopto.org/laphrodite/');
define('mail_domain','rov.hopto.org');

define('defaultTitle','');
define('defaultKeywords','');
define('defaultDescription','');
define('defaultMeta','');

define('defaultLangage','fr');
setlocale (LC_ALL, 'fr_FR.utf8','fra');

ini_set('display_errors', 1);
//set_time_limit(0);

?>