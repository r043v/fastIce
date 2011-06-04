<?php // fastIce global config

define ('template','mytemplate');

define('redisServer','127.0.0.1');
define('redisPrefix','laphrodite');

define('site_url','/'); // /subfolder/ can be used
define('site_full_url','http://mysite.com/'); // if sub folder set to http://mysite.com/subfolder/
define('mail_domain','mysite.com');

define('defaultTitle','');
define('defaultKeywords','');
define('defaultDescription','');
define('defaultMeta','');

define('defaultLangage','fr');
setlocale (LC_ALL, 'fr_FR.utf8','fra');

ini_set('display_errors', 1);
//set_time_limit(0);

?>