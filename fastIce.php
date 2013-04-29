<?php
/* *** ** * fastIce Framework core. \*
** *
*	fastIce beta 0.8.31 © 2010~2013 noferi Mickaël/m2m - noferov@gmail.com - Some Rights Reserved.

	Except where otherwise noted, this work is licensed under a Creative Commons Attribution 3.0 License, CC-by-nc-sa
*	terms of licence CC-by-nc-sa are readable at : http://creativecommons.org/licenses/by-nc-sa/3.0/
** *
* ** *** */
include 'config.php';
/* *********************.  ..**/

// declare and initialize global framework vars *
global $redis,$globalCacheSave,$nofollow,$noDesignCache,$noDesignCacheAtAll,$noDesignCacheUsed,$renderInclude,$global_current_file,$designPath,$commonDesignPath,$currentDesign,$currentLangage,$urlPath,$seedKey,$currentPlugin,$design_cache,$userCanonical;
$globalCacheSave=array();$nofollow=0;$noDesignCache=0;$noDesignCacheAtAll=0;$noDesignCacheUsed=0;$renderInclude=array();$global_current_file='';$designPath='';$commonDesignPath='';$currentPlugin='';$currentLangage=defaultLangage;$userCanonical=false;

// define info not user dependant *
define ('site_full_path',dirname(__FILE__));
define ('site_full_url','http://'.domain_name.site_url);

// check php-redis is loaded into php api *
if(!extension_loaded('redis')) die('please install php extension <a href="https://github.com/nicolasff/phpredis">php-redis</a>.');

function debug($name){ global $redis; $redis->set('msg',$name); }

// connect to redis-server *
$redis = new Redis(); try { if(false === $redis->connect(redisServer)) die('unable to reach redis.'); } catch (Exception $e) { die('please check that redis server at "'.redisServer.'" is up ! : <i>'.$e->getMessage().'</i>'); }

// function to retrieve page and url info *
function getLang(){global $currentLangage;return $currentLangage;} // return the current used language
function getUrlPath(){global $urlPath;return $urlPath;} // return current brut url args with '/', example : 'toto/titi/tata'
function getPageName(){global $seedKey;return $seedKey;} // return current page name, example : 'index'
function getUrlKey(){ global $currentLangage,$seedPath; return $currentLangage.':'.$seedPath; }
function getFullUrl()	{	global $currentLangage,$seedPath; $out=''; if($currentLangage != defaultLangage) $out=$currentLangage.'/';
				if($seedPath[0] == '/') return $out.substr($seedPath,1); return $out.$seedPath;
			}

// additional keywords for render *
function addToRender($word,$txt) { global $renderInclude,$currentDesign; $renderInclude[$word] .= $txt; setDesignCache($currentDesign.'/:'.$word,$renderInclude[$word]); setDesignCache($currentDesign.'/:addition',true); }
function setRender($word,$txt) { global $renderInclude,$currentDesign; $renderInclude[$word] = $txt; setDesignCache($currentDesign.'/:'.$word,$txt); setDesignCache($currentDesign.':[addition',true); }
function extendRenderWords($word) { if(!isset($renderWords[$word])) array_push($renderWords,$word); }
global $renderWords; $renderWords = array('head','js','jquery','title','meta','style','keywords','description','body');

function addToHead($t){addToRender('head',$t);}$renderInclude['head']='';function addToJs($t){addToRender('js',$t);}$renderInclude['js']='';function addToJquery($t){addToRender('jquery',$t);}$renderInclude['jquery']='';function addToStyle($t){addToRender('style',$t);}$renderInclude['style']='';function addToBody($t){addToRender('body',$t);}$renderInclude['body']='';
function addToTitle($t){addToRender('title',$t);}function setTitle($t){setRender('title',$t);}$renderInclude['title']='';function addToMeta($t){addToRender('meta',$t);}function setMeta($t){setRender('meta',$t);}$renderInclude['meta']='';function addToKeywords($t){addToRender('keywords',$t);}function setKeywords($t){setRender('keywords',$t);}$renderInclude['keywords']='';function addToDescription($t){addToRender('description',$t);}function setDescription($t){setRender('description',$t);}$renderInclude['description']='';
function setCanonical($c){ global $userCanonical,$currentLangage;$userCanonical=site_full_url.$currentLangage.$c; }
function getCanonical(){ global $userCanonical; return $userCanonical; }

function addToRenderOnce($word,$id,$txt)
{	static $filesinc, $loaded = 0;
	global $currentDesign;
	if(!$loaded)
	{	$filesinc = getDesignCache(':filesinc');
		if($filesinc === false) $filesinc = array(); else $filesinc = unserialize($filesinc);
		$loaded=1;
	}

	$key=md5($word.$id); if(isset($filesinc[$key])) return; $filesinc[$key]=1;
	setDesignCache(':filesinc',serialize($filesinc)); addToRender($word,$txt);
}

// include or insert into head, once, a js or css file *
function includeJs($path){if(false === strstr($path,'http://')) $upath=site_url.$path; else $upath=$path; addToRenderOnce('head',$path,'<script type="text/javascript" src="'.$upath.'"></script>');}
function includeCss($path){if(false === strstr($path,'http://')) $upath=site_url.$path; else $upath=$path; addToRenderOnce('head',$path,'<link rel="stylesheet" type="text/css" href="'.$upath.'" />');}
function insertJs($path){addToRenderOnce('js',$path,file_get_contents($path));}
function insertCss($path){addToRenderOnce('style',$path,file_get_contents($path));}

// master function, will return the complete page ready to be echo *
function renderPage($url,$langage,$upath,$callback=false,$skeleton=defaultSkeletonName,$json=false)
{	global $renderInclude, $design_cache, $canonicalurl, $currentLangage, $noDesignCacheUsed, $urlPath, $designPath, $commonDesignPath, $global_current_file, $need_fix_name, $redis, $seedPath, $seedKey, $globalCacheSave, $noDesignCacheAtAll, $userCanonical;
	$currentLangage=$langage; $urlPath=$upath; $designPath=$url; $commonDesignPath=''; $seedPath=$urlPath.'/'.$url; $seedKey=$url; // fill global vars

	$cachekey = redisPrefix.':cache:'.$skeleton.':'.$currentLangage.':'.$seedPath;
	
	if($langage!=defaultLangage) // generate canonical url
	{	$canonicalurl=site_full_url.$langage.'/';
		if($upath != '') $canonicalurl.=$upath.'/';
	} else
	{	$canonicalurl=site_full_url;
		if($upath != '') $canonicalurl.=$upath.'/';
	}

	// get page cache
	if(empty($design_cache))
	{	$r = $redis->multi(Redis::PIPELINE)->hgetall($cachekey)->expire($cachekey,cacheTTL);
		$cache = $r->exec()[0];

		$design_cache = $cache;
	}
	
	// check cache content of .ini entry for skeleton name, if not set, define at default.
	if(!isset($design_cache[':skeleton'])) $design_cache[':skeleton'] = $skeleton;
	
	// if page cache contain gzip entry, directly return it.
	if(isset($design_cache['html']))
	{	if(isset($design_cache['gzip'])) { header("X-Compression: gzip"); header("Content-Encoding: gzip"); }
		return $design_cache['html'];
	}

	// check page skeleton was loaded, else, load and cache it.
	if(!isset($design_cache[':sk']))
	{	$path = site_full_path.'/'.template.'/'.common_path.'/skeleton/'.$design_cache[':skeleton'].'.html';
		if(is_file($path))
		{	$page = file_get_contents($path); $redis->hset($cachekey,':sk',$page);
		} else {
			//die(site_full_path.' => '.getcwd().' => '.$path);
			if($skeleton !== false) $page = $skeleton; else $page = defaultSkeleton;
		}
	} else $page = $design_cache[':sk'];

	// launch page part parsing, seed is skeleton
	$page = parsePage($url,$page);

	// if anywhere parsing was need to let page part as this, replace parse maker with page part one.
	if($need_fix_name) $page = str_replace('[$$]','§', $page);

	if($callback !== false) $callback($page); // any last chance callback ?

	// verifies and assign page final info
	if(empty($renderInclude['title']))
	{	if(isset($design_cache[':title']))
			$renderInclude['title'] = $design_cache[':title'];
		else	$renderInclude['title'] = defaultTitle;
	}

	if(empty($renderInclude['keywords']))
	{	if(isset($design_cache[':keywords']))
			$renderInclude['keywords'] = $design_cache[':keywords'];
		else	$renderInclude['keywords'] = defaultKeywords;
	}

	if(empty($renderInclude['description']))
	{	if(isset($design_cache[':description']))
			$renderInclude['description'] = $design_cache[':description'];
		else	$renderInclude['description'] = defaultDescription;
	}

	if(empty($renderInclude['meta']))
	{	if(isset($design_cache[':meta']))
			$renderInclude['meta'] = $design_cache[':meta'];
		else	$renderInclude['meta'] = defaultMeta;
	}

	if($userCanonical === false) $canonical = $canonicalurl.$url; else $canonical = $userCanonical;
	
	// generate head insertion
	$renderInclude['meta'] .= '<title>'.$renderInclude['title'].'</title><meta name="keywords" content="'.$renderInclude['keywords'].'" /><meta name="description" content="'.$renderInclude['description'].'" /><meta name="generator" content="fastIce" /><link rel="canonical" href="'.$canonicalurl.$url.'" />';
	$renderInclude['head']  = $renderInclude['meta'].'<script type="text/javascript" src="'.jqueryLocation.'"></script>'.$renderInclude['head'].'[js]';
	if(!empty($renderInclude['style'])) $renderInclude['head'] .= '<style>'.$renderInclude['style'].'</style>';

	// generate complete brut page, depend on js is used or not
	if(empty($renderInclude['js']) && empty($renderInclude['jquery'])) $renderInclude['js']='';  // js free
	 else // js used
	{	if(!$json) $renderInclude['js'] = '<script type="text/javascript">'.$renderInclude['js'].'$(document).ready(function(){'.$renderInclude['jquery'].'});</script>';
		if(!$noDesignCacheUsed && extension_loaded('jsmin')) $renderInclude['js'] = jsmin($renderInclude['js']);
	}
	
	$completePage = str_replace(array('[head]','[body]','[js]','[url]','[lang]','[title]'),array($renderInclude['head'],$renderInclude['body'],$renderInclude['js'],site_url,$currentLangage,$renderInclude['title']),$page);

	if($json)
	{	$renderInclude['html'] = $completePage;
		$completePage = json_encode($renderInclude);
	}
	
	if(!$noDesignCacheAtAll)
	{	if(!$noDesignCacheUsed) // the page is fully in cache
		{	global $redis,$seedPath,$currentLangage;
			$redis->multi(Redis::PIPELINE)->del($cachekey); // delete old key

			// if compression enabled, gz the output at full compression and add gz flag in the page cache.
			if(enable_gz_compression)
			{	$completePage =  gzencode($completePage,9);
				$redis->hset($cachekey,'gzip',1);
				header("X-Compression: gzip"); header("Content-Encoding: gzip"); // send gz header
			}

			// global page cache save and return the gz or html data.
			$redis->hset($cachekey,'html',$completePage)->expire($cachekey,cacheTTL)->exec();
			return $completePage;
		}

		// save all the page caches in redis, in one request
		if(!empty($globalCacheSave)){ $redis->multi(Redis::PIPELINE)->hMset($cachekey,$globalCacheSave)->expire($cachekey,cacheTTL)->exec(); }
	} else { $redis->del($cachekey); }

	if(enable_gz_compression && gz_compression) // out gz compression is forced
	{	$completePage = gzencode($completePage,gz_compression);
		header("X-Compression: gzip"); header("Content-Encoding: gzip"); // send gz header
	}

	return $completePage;
}

// recursive page parsing function, work on skeleton and search for §part§ *
function parsePage($key,$out=false)
{	global $global_current_file,$designPath,$commonDesignPath,$renderInclude;

 	$dpath = $designPath; $cdpath = $commonDesignPath;
	$global_current_file = $key;

	if($out === false)
	{	$out = getDesign($key);
		$designPath .= '/'.$key; $commonDesignPath .= '/'.$key;
	}

	if($out !== false)
	{	$offset=0;
		while(1)
		{	$start = strpos($out,'§',$offset);
			if($start === false) break;
			$off = $start+2;
			$end = strpos($out,'§',$off);
			if($end === false) break;
			$size = $end-$off;
			$word = substr($out,$off,$size);
			$out = substr_replace($out,parsePage($word),$start,$size+4);
			$offset = $start;
		}
	}

	// /* generate next line in brace content ! */ global $renderWords,$renderInclude; foreach($renderWords as $w) print '$d=getDesignCache(\':'.$w.'\');if($d!==false){if(!isset($renderInclude[\''.$w.'\']))$renderInclude[\''.$w.'\']=$d;else $renderInclude[\''.$w.'\'].=$d;}';die();
	if(getDesignCache(':addition') !== false){ $d=getDesignCache(':head');if($d!==false){if(!isset($renderInclude['head']))$renderInclude['head']=$d;else $renderInclude['head'].=$d;}$d=getDesignCache(':body');if($d!==false){if(!isset($renderInclude['body']))$renderInclude['body']=$d;else $renderInclude['body'].=$d;}$d=getDesignCache(':js');if($d!==false){if(!isset($renderInclude['js']))$renderInclude['js']=$d;else $renderInclude['js'].=$d;}$d=getDesignCache(':jquery');if($d!==false){if(!isset($renderInclude['jquery']))$renderInclude['jquery']=$d;else $renderInclude['jquery'].=$d;}$d=getDesignCache(':title');if($d!==false){if(!isset($renderInclude['title']))$renderInclude['title']=$d;else $renderInclude['title'].=$d;}$d=getDesignCache('meta');if($d!==false){if(!isset($renderInclude['meta']))$renderInclude['meta']=$d;else $renderInclude['meta'].=$d;}$d=getDesignCache(':style');if($d!==false){if(!isset($renderInclude['style']))$renderInclude['style']=$d;else $renderInclude['style'].=$d;}$d=getDesignCache(':keywords');if($d!==false){if(!isset($renderInclude['keywords']))$renderInclude['keywords']=$d;else $renderInclude['keywords'].=$d;}$d=getDesignCache(':description');if($d!==false){if(!isset($renderInclude['description']))$renderInclude['description']=$d;else $renderInclude['description'].=$d;} }

	$designPath = $dpath; $commonDesignPath = $cdpath;
	return $out;
}

function noDesignCacheAtAll()
{	global $noDesignCacheAtAll; $noDesignCacheAtAll=1;
}

function noDesignCache()
{	global $noDesignCache; $noDesignCache=1;
}

// put anything in cache *
function setDesign($design,$content)
{	global $designPath,$design_cache;
	$design_cache[$designPath.'/'.$design]=$content;
	//die($designPath.'/'.$design);
}

function gsetDesign($design,$content)
{	global $design_cache;
	$design_cache[$design]=$content;
}

// put anything in cache, at the current page part tree *
function setDesignCache($design,$content)
{	global $noDesignCache,$designPath,$noDesignCacheUsed,$noDesignCacheAtAll,$globalCacheSave;
	if($noDesignCacheAtAll || $noDesignCache || isset($_SESSION['user']))
		$noDesignCacheUsed=1;
	else	$globalCacheSave[$designPath.'/'.$design]=$content;
}

// get anything from cache, at the current page part tree *
function getDesignCache($design)
{	global $design_cache,$designPath,$redis;
	$k = $designPath.'/'.$design; if(isset($design_cache[$k])) return $design_cache[$k];
	return false;
}

// will retrieve a page part, from cache, files or plugin *
function getDesign($design)
{	if(empty($design)) return ''; $d = getDesignCache($design); if($d !== false) return $d;
	global $nofollow,$last_design,$need_fix_name,$redis,$noDesignCache,$currentDesign,$designPath,$commonDesignPath,$currentLangage;
	$noDesignCache=0; $currentDesign=$design;
	if($design == 'nofolow'){ $nofollow=1; $need_fix_name=1; return ''; } else if($design == 'folow'){ $nofollow=0; return ''; } if($nofollow) { return '[$$]'.$design.'[$$]'; }

	if(false === strstr($design,'|'))
	{	// search design in the template folder, absolute path with lang prefix
		$path = template.'/'.$designPath.'/'.$currentLangage.'.'.$design.'.php'; // template folder
		if(is_file($path)){ob_start();include($path);$d=ob_get_contents();ob_end_clean();setDesignCache($design,$d);return $d;}

		// search design in the template folder, absolute path
		$path = template.'/'.$designPath.'/'.$design.'.php'; // template folder
		if(is_file($path)){ob_start();include($path);$d=ob_get_contents();ob_end_clean();setDesignCache($design,$d);return $d;}

		// search design in redis keys, absolute path with lang prefix
		$path = redisPrefix.':design:'.template.'/'.$designPath.'/'.$currentLangage.'.'.$design;
		$d = $redis->get($path); if(false !== $d){setDesignCache($design,$d);return $d;}

		// search design in the common template folder, absolute path with lang prefix
		$path = template.'/'.common_path.$commonDesignPath.'/'.$currentLangage.'.'.$design.'.php';
		if(is_file($path)){ob_start();include($path);$d=ob_get_contents();ob_end_clean();setDesignCache($design,$d);return $d;}

		// search design in the common template folder, absolute path
		$path = template.'/'.common_path.$commonDesignPath.'/'.$design.'.php';
		if(is_file($path)){ob_start();include($path);$d=ob_get_contents();ob_end_clean();setDesignCache($design,$d);return $d;}

		// search design in the common template folder, just file, no path, lang prefix
		$path = template.'/'.common_path.'/'.$currentLangage.'.'.$design.'.php';
		if(is_file($path)){ob_start();include($path);$d=ob_get_contents();ob_end_clean();setDesignCache($design,$d);return $d;}

		// search design in the common template folder, just file, no path
		$path = template.'/'.common_path.'/'.$design.'.php';
		if(is_file($path)){ob_start();include($path);$d=ob_get_contents();ob_end_clean();setDesignCache($design,$d);return $d;}

		// search design in constant files
		global $global_constants,$seedPath,$seedKey;
		if(!isset($global_constants))
		{	$global_constants = array();
			// common constants
			$path = template.'/'.common_path.'/'.design_path;
			if(is_file($path)) $constants = parse_ini_file($path,true);

			// page specific constants
			$path = template.'/'.$seedKey.'/'.design_path;
			if(is_file($path)) $constants = array_merge_recursive($constants,parse_ini_file($path,true));
			if(isset($constants))
			{	//print '<pre>';print_r($constants);print '</pre>';
				foreach($constants as $name=>$sub)
				{	if($name == $currentLangage || $name == 'common')
						foreach($sub as $cnt=>$val) $global_constants[$cnt] = $val;
				}
			}
		}

		if(isset($global_constants[$design])){$d=$global_constants[$design];setDesignCache($design,$d);return $d;}
	}
	else
	{	$args = explode('|',$design);
		$mdl = array_shift($args);
		$fn = 'fn_'.$mdl;
		ob_start();
		global $currentPlugin; $cplg=$currentPlugin; $currentPlugin=$mdl;
		if(function_exists($fn)) $fn($args);
		else	{	$path = site_full_path.'/'.module_path.'/'.$mdl.'/'.$mdl.'.php';
				if(is_file($path))
				{	include($path); if(function_exists($fn)) $fn($args);
				} else die('plugin '.$mdl.' not found ! path : '.$path);
			}
		$currentPlugin=$cplg;
		$out = ob_get_contents(); ob_end_clean();
		setDesignCache($design,$out); return $out;
	}

	// design is finally not found !
	//if(isUserPrivilege('show-error'))
	{	// css error msg
		//addToRenderOnce('style','span.red{color:red;} span.big{font-style:italic;font-weight:bold}');
		// generate html to draw some page info
		return '<p>design <span class="red big">'.$design.'</span> not found!</p><p>page : <span class="big">'.getPageName().'</span> language : <span class="big">'.getLang().'</span></p><p>cache search : '.$designPath.'/'.$design.'</p><p><span class="big">/</span> for file search is relative at <span class="big">'.site_full_path.'</span></br><br/>physical file possible path, ordered by engine search priority :</br><ul><li>'.site_url.template.'/'.$designPath.'/'.$currentLangage.'.'.$design.'.php</li><li>'.site_url.template.'/'.$designPath.'/'.$design.'.php</li><li>'.site_url.template.'/'.common_path.$commonDesignPath.'/'.$currentLangage.'.'.$design.'.php</li><li>'.site_url.template.'/'.common_path.$commonDesignPath.'/'.$design.'.php'.'</li><li>'.site_url.common_path.$commonDesignPath.'/'.$currentLangage.'.'.$design.'.php<li>'.site_url.common_path.$commonDesignPath.'/'.$design.'.php</li><li>'.site_url.template.'/'.common_path.'/'.$currentLangage.'.'.$design.'.php</li><li>'.site_url.template.'/'.common_path.'/'.$design.'.php</li><li>'.site_url.common_path.'/'.$currentLangage.'.'.$design.'.php</li><li>'.site_url.common_path.'/'.$design.'.php</li></ul><br/>constant files url :</br><ul><li>'.site_url.template.'/'.$seedKey.'/'.design_path.'</li><li>'.site_url.template.'/'.common_path.'/'.design_path.'</li></ul></p>';
	}// else return '';
}

// get current plugin name, designed to be used from plugin *
function getCurrentPlugin(){ global $currentPlugin; return $currentPlugin; }

// get current plugin http emplacement, designed to be used from plugin for ajax plugin neighbor files call *
function getCurrentPluginUrl(){ global $currentPlugin; return site_url.module_path.'/'.$currentPlugin.'/'; }

// will load a plugin if this one is not loaded *
function needPlugin($plg)
{	$fn = 'fn_'.$plg; global $currentPlugin; $cplg=$currentPlugin; $currentPlugin=$plg;
	if(function_exists($fn)) return true;
	$path = site_full_path.'/'.module_path.'/'.$plg.'/'.$plg.'.php';
	if(is_file($path)) { include($path); $currentPlugin=$cplg; return true; } $currentPlugin=$cplg; return false;
}

// launch a plugin *
function callPlugin($plg,$args)
{	if(false === needPlugin($plg)) return false;
	$fn = 'fn_'.$plg; global $currentPlugin; $cplg=$currentPlugin; $currentPlugin=$plg;
	ob_start(); $fn($args); $out = ob_get_contents(); ob_end_clean();
	$currentPlugin=$cplg; return $out;
}

function _fillDesign($data, $design, $callback=false)
{	$keywords = array(); $offset = 0; $arrayin = array(); $out = '';

	while(1)
	{	$start = strpos($design,'$',$offset);
		if($start === false) break;
		$off = $start+1;
		$end = strpos($design,'$',$off);
		if($end === false) break;
		$size = $end-$off;
		$word = substr($design,$off,$size);
		if(!isset($keywords[$word]))
		{	$keywords[$word] = $word;
			$arrayin[$word]  = '$'.$word.'$';
		} $offset = $end+1;
	}

	if($callback !== false)
	{	$n=0;
		if(is_array($callback)) // callback is function array
		{	foreach($data as $k => $dta)
			{	$out .= str_replace($arrayin,array_map(function($w)use($callback,$dta,$n,$k)
				{	if(isset($callback[$w]))
					{	if(isset($dta[$w])) return $callback[$w]($dta[$w],$n,$k);
						return $callback[$w]($dta,$n,$k);
					} else { if(isset($dta[$w])) return $dta[$w]; return ''; }
				},$keywords),$design); $n++;
			}
		}
	      else // callback is a generic function, who get word and complete data
		{	foreach($data as $k => $dta)
			{	$out .= str_replace($arrayin,array_map(function($w)use($callback,$dta,$n,$k){return $callback($w,$dta,$n,$k);},$keywords),$design); $n++;
			}
		}
	} else foreach($data as $k => $dta) $out .= str_replace($arrayin,array_map(function($w)use($dta,$k){if($w=='refkey')return $k; if(isset($dta[$w])) return $dta[$w]; return '';},$keywords),$design);

	return $out;
}

// will fill a design with some data, generic function, syntax is same as page part, but $ instead of §. some callback can be launch to manage data between get and draw. *
function fillDesign($data, $design, $callback=false)
{	global $noDesignCache; $save=$noDesignCache; $noDesignCache=0; $design=getDesign($design); $noDesignCache=$save;
	return _fillDesign($data,$design,$callback);
}

function filterFillDesign($data, $design, $callback=false,$filter=false)
{	if($filter === false)
		return _fillDesign($data,$design,$callback);
	return	_fillDesign(array_filter($data,$filter),$design,$callback);
}

function getFillTemplate($path)
{	return file_get_contents(site_full_path.'/'.template.'/common/skeleton/template/'.$path.'.txt');
}

// function designed to check user right, will be in plugin in future. *
function isUserPrivilege($prv)
{ return ( isset($_SESSION['user']) && (( isset($_SESSION['user']['right:all']) && !isset($_SESSION['user']['right:'.$prv]) ) || ( isset($_SESSION['user']['right:'.$prv]) &&  $_SESSION['user']['right:'.$prv] )));
}

function isUserLogued(){return isset($_SESSION['user']);}
function adminSecurityCheck(){ if(!isset($_SESSION['user'])) die('not logued in.'); }
function insertAdminHeadNeed(){ return '<link rel="stylesheet" type="text/css" href="'.site_url.module_path.'/administration/style.iframe.css" /><script type="text/javascript" src="'.str_replace('[url]',site_url,jqueryLocation).'"></script>'; }
function insertJquery(){ return '<script type="text/javascript" src="'.str_replace('[url]',site_url,jqueryLocation).'"></script>'; }

?>
