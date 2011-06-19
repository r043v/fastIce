<?php
/* *** ** * fastIce Framework core. \*
** *
*	fastIce alpha 0.6.0 © 2010~2011 noferi Mickaël/m2m - noferov@gmail.com - Some Rights Reserved.

	Except where otherwise noted, this work is licensed under a Creative Commons Attribution 3.0 License, CC-by-nc-sa
	terms of licence CC-by-nc-sa are readable at : http://creativecommons.org/licenses/by-nc-sa/3.0/
*
** *
* ** *** */
include 'config.php';
/* *********************.  ..**/

define('site_full_url','http://'.domain_name.site_url);

global $nofollow,$nodesigncache,$noDesignCacheUsed,$norendercache,$renderInclude,$redis,$global_current_file,$fnc,$designPath,$commonDesignPath,$currentDesign,$currentLangage,$urlpath,$seedKey,$currentPlugin;
$redis = new Redis(); if(!$redis->connect(redisServer)) die('cannot rape the database ...');

function setInfo($langage,$upath)
{	global $currentLangage,$canonicalurl,$urlpath;$currentLangage=$langage;$urlpath=$upath;
	if($langage!=defaultLangage)
	{	$canonicalurl=site_full_url.$langage.'/';
		if($upath != '') $canonicalurl.=$upath.'/';
	}
	else
	{ $canonicalurl=site_full_url;
	  if($upath != '') $canonicalurl.=$upath.'/';
	}
}

function getlang(){global $currentLangage;return $currentLangage;}
function getUrlPath(){global $urlpath;return $urlpath;}
function getPageName(){global $seedKey;return $seedKey;}

define ('site_full_path',dirname(__FILE__));
define ('site_path',site_full_path.'/files');

function logTo($file,$content){$f=fopen($file,'a') or die('can\'t open file "'.$file.'"');fwrite($f,$content);fclose($f);}

$nofollow=0;$nodesigncache=0;$norendercache=0;$renderInclude=array();$global_current_file='';$fnc=array();$designPath='';$commonDesignPath='';$currentPlugin='';$noDesignCacheUsed=0;
/* additionnal keywords for render */
function addToRender($word,$txt) { global $renderInclude,$currentDesign; $renderInclude[$word] .= $txt; setDesignCache($currentDesign.'/'.$word,$renderInclude[$word]); /*logTo('log.txt',"\n\n\n".'add to '.$word.' : '.$txt."\n\n".'current '.$word.' : '.$renderInclude[$word]);*/ }
function setRender($word,$txt) { global $renderInclude,$currentDesign; $renderInclude[$word] = $txt; setDesignCache($currentDesign.'/'.$word,$txt); }
function extendRenderWords($word) { if(!isset($renderWords[$word])) array_push($renderWords,$word); }
global $renderWords; $renderWords = array('head','js','jquery','title','meta','style','keywords','description','body');

function addToHead($t){addToRender('head',$t);}function setHead($t){setRender('head',$t);}$renderInclude['head']='';function addToJs($t){addToRender('js',$t);}function setJs($t){setRender('js',$t);}$renderInclude['js']='';function addToJquery($t){addToRender('jquery',$t);}function setJquery($t){setRender('jquery',$t);}$renderInclude['jquery']='';function addToTitle($t){addToRender('title',$t);}function setTitle($t){setRender('title',$t);}$renderInclude['title']='';function addToMeta($t){addToRender('meta',$t);}function setMeta($t){setRender('meta',$t);}$renderInclude['meta']='';function addToStyle($t){addToRender('style',$t);}function setStyle($t){setRender('style',$t);}$renderInclude['style']='';function addToKeywords($t){addToRender('keywords',$t);}function setKeywords($t){setRender('keywords',$t);}$renderInclude['keywords']='';function addToDescription($t){addToRender('description',$t);}function setDescription($t){setRender('description',$t);}$renderInclude['description']='';function addToBody($t){addToRender('body',$t);}function setBody($t){setRender('body',$t);}$renderInclude['body']='';
//foreach($renderWords as $word){ print('function addTo'.ucfirst($word).'($t){addToRender(\''.$word.'\',$t);}function set'.ucfirst($word).'($t){setRender(\''.$word.'\',$t);}$renderInclude[\''.$word.'\']=\'\';'); } die();

global $filesinc; $filesinc = array();
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

function includeJs($path){$out='<script type="text/javascript" src="'.site_url.$path.'"></script>'; addToRenderOnce('head',$path,$out);}
function includeCss($path){$out='<link rel="stylesheet" type="text/css" href="'.site_url.$path.'" />'; addToRenderOnce('head',$path,$out);}

// direct inject javascript file content into the js head section
function insertJs($path){addToRenderOnce('js',$path,file_get_contents($path));}

// direct inject css file content into the style head section
function insertCss($path){addToRenderOnce('style',$path,file_get_contents($path));}

function renderPage($url,$callback='')
{	$page = parsePage($url); // get brut html from parser

	if(!empty($callback)) $callback(); // any last chance callback ?

	// verifies and assign page final info
	global $renderInclude, $design_cache, $canonicalurl, $currentLangage, $noDesignCacheUsed;
	if(empty($renderInclude['title'])) $renderInclude['title'] = $design_cache['ini:title'];// else print ' title : '.$renderInclude['title'];
	if(empty($renderInclude['keywords'])) $renderInclude['keywords'] = $design_cache['ini:keywords'];
	if(empty($renderInclude['description'])) $renderInclude['description'] = $design_cache['ini:description'];
	if(empty($renderInclude['meta'])) $renderInclude['meta'] = $design_cache['ini:meta'];
	// generate head render insertion
	$renderInclude['meta'] .= '<title>'.$renderInclude['title'].'</title><meta name="keywords" content="'.$renderInclude['keywords'].'" /><meta name="description" content="'.$renderInclude['description'].'" /><meta name="generator" content="fastIce" /><link rel="canonical" href="'.$canonicalurl.$url.'" />';

	//<script>if(typeof jQuery == "undefined"){document.write(\'<script src="'.site_url.'js/jquery.min.js"></script>\');}</script>

	$renderInclude['head']  = ''.$renderInclude['meta'].$renderInclude['head'].'[js]';
	if(!empty($renderInclude['style'])) $renderInclude['head'] .= '<style>'.$renderInclude['style'].'</style>';

	// is page not js free ?
	if(!empty($renderInclude['js']) || !empty($renderInclude['jquery']))
	{	$renderInclude['js'] = '<script type="text/javascript">'.$renderInclude['js'].'$(document).ready(function(){'.$renderInclude['jquery'].'});</script>';

		// if possible use global page cache instead of page part fragmented cache
		if(!$noDesignCacheUsed)
		{	// try to minifies the javascript
			if(extension_loaded('jsmin')) $renderInclude['js'] = jsmin($renderInclude['js']);

			// global cache save

			$completePage =  str_replace(array('[head]','[body]','[url]','[lang]','[js]'),array($renderInclude['head'],$renderInclude['body'],site_url,$currentLangage,$renderInclude['js']),$page);

			return $completePage;
		}

		return str_replace(array('[head]','[body]','[url]','[lang]','[js]'),array($renderInclude['head'],$renderInclude['body'],site_url,$currentLangage,$renderInclude['js']),$page);
	}

	return str_replace(array('[head]','[body]','[url]','[lang]','[js]'),array($renderInclude['head'],$renderInclude['body'],site_url,$currentLangage,''),$page);
}

function get_include_contents($filename)
{	if(!is_file($filename)) return false;
	ob_start(); include ($filename); $contents = ob_get_contents(); ob_end_clean(); return $contents;
}

function parsePage($key,$seed=1)
{	global $fnc,$global_current_file,$need_fix_name,$designPath,$commonDesignPath,$redis;

	if($seed){ $designPath=$key; $commonDesignPath=''; }

 	$dpath = $designPath; $cdpath = $commonDesignPath;

	$out = '';
	if($seed)
	{	global $seedPath,$seedKey,$design_cache,$currentLangage,$urlpath; $seedPath=$urlpath.'/'.$key; $seedKey=$key;

		if(isset($_GET['f5']) || isset($_SESSION['user']))
		{ $redis->delete(redisPrefix.':designCache:'.$currentLangage.':'.$seedPath);
		} else if(isset($_GET['deleteCache'])){$cache = $redis->keys(redisPrefix.':designCache:*');foreach($cache as $c)$redis->delete($c);}

		$design_cache = $redis->hgetall(redisPrefix.':designCache:'.$currentLangage.':'.$seedPath);

		if(!isset($design_cache['ini:loaded'])) // redis page info not set
		{	$page_opt = array('loaded'=>1);
			$path = template.'/'.$key.'/'.$key.'.ini';
			if(is_file($path)) $page_opt = array_merge(parse_ini_file($path),$page_opt);
			$outarray = array(); foreach($page_opt as $name=>$value) $outarray['ini:'.$name] = $value;
			$design_cache = array_merge($design_cache,$outarray);
			$redis->hMset(redisPrefix.':designCache:'.$currentLangage.':'.$seedPath,$outarray);
		}

		if(!isset($design_cache['ini:skeleton'])) $design_cache['ini:skeleton'] = 'normal';
		if(!isset($design_cache['ini:title'])) $design_cache['ini:title'] = defaultTitle;
		if(!isset($design_cache['ini:keywords'])) $design_cache['ini:keywords'] = defaultKeywords;
		if(!isset($design_cache['ini:description'])) $design_cache['ini:description'] = defaultDescription;
		if(!isset($design_cache['ini:meta'])) $design_cache['ini:meta'] = defaultMeta;

		if(!isset($design_cache['ini:sk']))
		{	$path = template.'/'.common_path.'/skeleton/'.$design_cache['ini:skeleton'].'.html';
			if(is_file($path))
			{	$out = file_get_contents($path); $redis->hset(redisPrefix.':designCache:'.$currentLangage.':'.$seedPath,'ini:sk',$out);
			} else	{
//					$out = '<h3>fastIce not configured !</h3><br>please have a look at config.php file, you got some info to give at the framework,<br>Also, you need going create the default page skeleton file : <b>'.$path.'</b><br>'.file_get_contents('http://fastice.tk/first.start.html');//<br>please have a look at config.php file, you got some info to give at the framework,<br><br>Also, you need going create the default page skeleton file : <b>'.$path.'</b><br><br>exemple file content :<br><br><i>&sect;header&sect;<br>&sect;content&sect;<br>&sect;footer&sect;</i><br><br>also you can put here all the html additional text you need, like all the template files, exemple :<br><br><i>&lt;html&gt;<br><bq>&sect;header&sect;<br><bq>&lt;body&gt;<br><bq><bq>&lt;div class="some-class"&gt;&sect;content&sect;&lt;/div&gt;<br>&sect;footer&sect;</bq><br>&lt;/body&gt;<br></bq>&lt;/html&gt;</bq></i><br><br>A good start is to come <a target="_blank" href="http://fastice.tk">on the framework homepage</a>';

					$out = defaultSkeleton;

				}
		} else $out = $design_cache['ini:sk'];
	} else {
		$global_current_file = $key;
		$out = getDesign($key);
		$designPath .= '/'.$key; $commonDesignPath .= '/'.$key;
	}

	global $renderInclude;
	// global $renderWords,$renderInclude; foreach($renderWords as $w) print '$d=getDesignCache(\''.$w.'\');if($d!==false){if(!isset($renderInclude[\''.$w.'\']))$renderInclude[\''.$w.'\']=$d;else $renderInclude[\''.$w.'\'].=$d;}';die();
	$d=getDesignCache('head');if($d!==false){if(!isset($renderInclude['head']))$renderInclude['head']=$d;else $renderInclude['head'].=$d;}$d=getDesignCache('body');if($d!==false){if(!isset($renderInclude['body']))$renderInclude['body']=$d;else $renderInclude['body'].=$d;}$d=getDesignCache('js');if($d!==false){if(!isset($renderInclude['js']))$renderInclude['js']=$d;else $renderInclude['js'].=$d;}$d=getDesignCache('jquery');if($d!==false){if(!isset($renderInclude['jquery']))$renderInclude['jquery']=$d;else $renderInclude['jquery'].=$d;}$d=getDesignCache('title');if($d!==false){if(!isset($renderInclude['title']))$renderInclude['title']=$d;else $renderInclude['title'].=$d;}$d=getDesignCache('meta');if($d!==false){if(!isset($renderInclude['meta']))$renderInclude['meta']=$d;else $renderInclude['meta'].=$d;}$d=getDesignCache('style');if($d!==false){if(!isset($renderInclude['style']))$renderInclude['style']=$d;else $renderInclude['style'].=$d;}$d=getDesignCache('keywords');if($d!==false){if(!isset($renderInclude['keywords']))$renderInclude['keywords']=$d;else $renderInclude['keywords'].=$d;}$d=getDesignCache('description');if($d!==false){if(!isset($renderInclude['description']))$renderInclude['description']=$d;else $renderInclude['description'].=$d;}

	if($out && $out!='')
	{	$offset=0;
		for(;;)
		{	$start = strpos($out,'§',$offset);
			if($start === false) break;
			$off = $start+2;
			$end = strpos($out,'§',$off);
			if($end === false) break;
			$size = $end-$off;
			$word = substr($out,$off,$size);
			$out = substr_replace($out,parsePage($word,0),$start,$size+4);
			$offset = $start;
		}
	} else $out = '';

	if($seed && $need_fix_name) $out = str_replace('[$$]','§', $out);
	$designPath = $dpath; $commonDesignPath = $cdpath;

	return $out;
}

function getArgs($string,$word,$separator)
{	$wordSize = strlen($word);
	if(substr($string,0,$wordSize) == $word)
	{	$args = substr($string,$wordSize);
		$args = explode($separator,$args);
		array_unshift($args,count($args));
		return $args;
	}	return array(0);
}

global $design_cache;
function noDesignCache(){global $nodesigncache;$nodesigncache=1;}
function setDesignCache($design,$content)
{	global $nodesigncache,$seedPath,$designPath,$currentLangage,$redis,$noDesignCacheUsed;
	if(!$nodesigncache && !isset($_SESSION['user']))
	{	$redis->hset(redisPrefix.':designCache:'.$currentLangage.':'.$seedPath,$designPath.'/'.$design,$content);
	} else { $noDesignCacheUsed=1; }
}

function getDesignCache($design)
{	global $design_cache,$designPath,$redis;
	$k = $designPath.'/'.$design; if(isset($design_cache[$k])) return $design_cache[$k];
	return false;
}

function getDesign($design)
{	if($design == '') return ''; $d = getDesignCache($design); if($d !== false) return $d;
	global $nofollow,$last_design,$need_fix_name,$redis,$nodesigncache,$currentDesign; $nodesigncache=0; $currentDesign=$design;
	if($design == 'nofolow'){ $nofollow=1; $need_fix_name=1; return ''; }
	if($design == 'folow'){   $nofollow=0; return ''; }
	if($nofollow) { return '[$$]'.$design.'[$$]'; }
	global $designPath,$commonDesignPath,$currentLangage;

	if(false === strstr($design,'|'))
	{
		// search design in the template folder, absolute path with lang prefix
		$path = template.'/'.$designPath.'/'.$currentLangage.'.'.$design.'.php'; // template folder
		if(file_exists($path)) { $d=get_include_contents($path);if($d!==false){setDesignCache($design,$d);return $d;}}

		// search design in the template folder, absolute path
		$path = template.'/'.$designPath.'/'.$design.'.php'; // template folder
		if(file_exists($path)) { $d=get_include_contents($path);if($d!==false){setDesignCache($design,$d);return $d;}}

		// search design in the common template folder, absolute path with lang prefix
		$path = template.'/'.common_path.$commonDesignPath.'/'.$currentLangage.'.'.$design.'.php';
		if(file_exists($path)) { $d=get_include_contents($path);if($d!==false){setDesignCache($design,$d);return $d;}}

		// search design in the common template folder, absolute path
		$path = template.'/'.common_path.$commonDesignPath.'/'.$design.'.php';
		if(file_exists($path)) { $d=get_include_contents($path);if($d!==false){setDesignCache($design,$d);return $d;}}

		// search design in the common folder, absolute path with lang prefix
		$path = common_path.$commonDesignPath.'/'.$currentLangage.'.'.$design.'.php';
		if(file_exists($path)) { $d=get_include_contents($path);if($d!==false){setDesignCache($design,$d);return $d;}}

		// search design in the common folder, absolute path
		$path = common_path.$commonDesignPath.'/'.$design.'.php';
		if(file_exists($path)) { $d=get_include_contents($path);if($d!==false){setDesignCache($design,$d);return $d;}}

		// search design in the common template folder, just file, no path, lang prefix
		$path = template.'/'.common_path.'/'.$currentLangage.'.'.$design.'.php';
		if(file_exists($path)) { $d=get_include_contents($path);if($d!==false){setDesignCache($design,$d);return $d;}}

		// search design in the common template folder, just file, no path
		$path = template.'/'.common_path.'/'.$design.'.php';
		if(file_exists($path)) { $d=get_include_contents($path);if($d!==false){setDesignCache($design,$d);return $d;}}

		// search design in the common folder, just file, no path, lang prefix
		$path = common_path.'/'.$currentLangage.'.'.$design.'.php';
		if(file_exists($path)) { $d=get_include_contents($path);if($d!==false){setDesignCache($design,$d);return $d;}}

		// search design in the common folder, just file, no path
		$path = common_path.'/'.$design.'.php';
		if(file_exists($path)) { $d=get_include_contents($path);if($d!==false){setDesignCache($design,$d);return $d;}}

		// search design in constant files
		global $global_constants,$seedPath,$seedKey;
		if(!isset($global_constants))
		{	//$global_constants = $redis->hgetall(redisPrefix.':designCache:'.$currentLangage.':'.$seedPath.':constant');
			//if(empty($global_constants))
			{	$global_constants = array();
				//print '<br>loading constants';
				// common constants
				$path = template.'/'.common_path.'/'.design_path;
				if(is_file($path)) $constants = parse_ini_file($path,true);

				//print '<pre>';print_r($constants);print '</pre>';

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
				//$redis->hmset(redisPrefix.':designCache:'.$currentLangage.':'.$seedPath.':constant',$global_constants);
				//print '<pre>';print_r($global_constants);print '</pre>';
			}
		}
		if(isset($global_constants[$design])){$d=$global_constants[$design];setDesignCache($design,$d);return $d;}
	}
	else
	{	$mdl = explode('|',$design);
		if(isset($mdl[0]))
		{	$mdl = $mdl[0];
			$mdlargs = getArgs($design,$mdl.'|','|');
			if($mdlargs[0] > 0)
			{	$fn = 'fn_'.$mdl;
				ob_start();
				global $currentPlugin; $cplg=$currentPlugin; $currentPlugin=$mdl;
				if(function_exists($fn)) $fn($mdlargs);
				else	{	$path = module_path.'/'.$mdl.'/'.$mdl.'.php';
						if(is_file($path))
						{	$currentPlugin=$cplg;
							include($path);
							if(function_exists($fn))
							{	$fn($mdlargs);
								$out = ob_get_contents(); ob_end_clean();
								setDesignCache($design,$out); return $out;
							}
						}
					}
			}
		}
	}

	// design is finally not found !

	if(isUserPrivilege('show-error'))
	{	// css error msg
		addToRenderOnce('style','	span.red{color:red;}
						span.big{font-style:italic;font-weight:bold}');
		// generate html to draw some page info
		$out = '<p>design <span class="red bi">'.$design.'</span> not found!</p><p>page : <span class="big">'.getPageName().'</span> language : <span class="big">'.getLang().'</span></p><p><span class="big">/</span> for file search is relative at <span class="big">'.site_full_path.'</span></br><br/>physical file possible path, ordered by engine search priority :</br><ul><li>'.site_url.template.'/'.$designPath.'/'.$currentLangage.'.'.$design.'.php</li><li>'.site_url.template.'/'.$designPath.'/'.$design.'.php</li><li>'.site_url.template.'/'.common_path.$commonDesignPath.'/'.$currentLangage.'.'.$design.'.php</li><li>'.site_url.template.'/'.common_path.$commonDesignPath.'/'.$design.'.php'.'</li><li>'.site_url.common_path.$commonDesignPath.'/'.$currentLangage.'.'.$design.'.php<li>'.site_url.common_path.$commonDesignPath.'/'.$design.'.php</li><li>'.site_url.template.'/'.common_path.'/'.$currentLangage.'.'.$design.'.php</li><li>'.site_url.template.'/'.common_path.'/'.$design.'.php</li><li>'.site_url.common_path.'/'.$currentLangage.'.'.$design.'.php</li><li>'.site_url.common_path.'/'.$design.'.php</li></ul><br/>constant files url :</br><ul><li>'.site_url.template.'/'.$seedKey.'/'.design_path.'</li><li>'.site_url.template.'/'.common_path.'/'.design_path.'</li></ul></p>';

		// maybe a plugin ? the case, draw plugin info too
		{	$mdl = explode('|',$design);
			if(isset($mdl[0]))
			{	$mdl = $mdl[0];
				$mdlargs = getArgs($design,$mdl.'|','|');
				if($mdlargs[0] > 0) $out.= '<span class="bi">seen plugin</span> <span class="bi red">'.$mdl.'</span>, please check <span class="bi">'.module_path.'/'.$mdl.'/'.$mdl.'.php</span> plugin file, and his <span class="bi">fn_'.$mdl.'($args)</span> function.';
			}
		}

		return $out;
	} else return '';
}

function getCurrentPlugin(){ global $currentPlugin; return $currentPlugin; }
function getCurrentPluginUrl(){ global $currentPlugin; return site_url.module_path.'/'.$currentPlugin.'/'; }

function needPlugin($plg)
{	$fn = 'fn_'.$plg; global $currentPlugin; $cplg=$currentPlugin; $currentPlugin=$plg;
	if(function_exists($fn)) { $currentPlugin=$cplg; return true; }
	$path = site_full_path.'/'.module_path.'/'.$plg.'/'.$plg.'.php';
	if(is_file($path)) { include($path); $currentPlugin=$cplg; return true; }
	$currentPlugin=$cplg; return false;
}

function callPlugin($plg,$args)
{	if(false === needPlugin($plg)) return false;
	$fn = 'fn_'.$plg; global $currentPlugin; $cplg=$currentPlugin; $currentPlugin=$plg;
	ob_start(); $fn($args); $out = ob_get_contents(); ob_end_clean();
	$currentPlugin=$cplg; return $out;
}

function fillDesign($data, $design, $callback=false)
{	global $nodesigncache; $savenodesign=$nodesigncache; $nodesigncache=0; $design=getDesign($design); $nodesigncache=$savenodesign;
	$keywords = array();
	$offset=0; // search all keywords in the design
	$arrayin = array();

	for(;;)
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

	$out = '';
	if($callback !== false)
	{	$n=0;
		if(is_array($callback)) // callback is function array
		{	foreach($data as $k => $dta)
			{	$out .= str_replace($arrayin,array_map(function($w)use($callback,$dta,$n,$k)
				{	if(isset($callback[$w]))
					{	if(isset($dta[$w])) return $callback[$w]($dta[$w],$n,$k);
						return $callback[$w]($dta,$n);
					} else return '';
				},$keywords),$design); $n++;
			}
		}
	      else
		{	// callback is a generic function, who get word and complete data
			foreach($data as $k => $dta)
			{	$out .= str_replace($arrayin,array_map(function($w)use($callback,$dta,$n,$k){return $callback($w,$dta,$n,$k);},$keywords),$design); $n++;
			}
		}
	} else foreach($data as $k => $dta) $out .= str_replace($arrayin,array_map(function($w)use($dta,$k){if($w=='refkey')return $k; if(isset($dta[$w])) return $dta[$w]; return '';},$keywords),$design);

	return $out;
}

function lredisHashFill($listkey, $design, $callback=false, $kprefix='', $ksuffix='', $start=0, $end=-1, &$size=0)
{	global $redis;
	list($keys,$size) = $redis->multi(Redis::PIPELINE)->lRange($listkey,$start,$end)->lSize($listkey)->exec();
	if(empty($keys)) return '';
	$redis->multi(Redis::PIPELINE); foreach($keys as $k) $redis->hgetall($kprefix.$k.$ksuffix);
	$data = array_combine($keys,$redis->exec());
	//print '<pre>';print_r($data);print '</pre>';
	return fillDesign($data,$design,$callback);
}

function sredisHashFill($setkey, $design, $callback=false, $kprefix='', $ksuffix='', $filter=false, $order=false, $preorder=false,&$nbentry=0)
{	global $redis; $keys = $redis->smembers($setkey); if(empty($keys)){ $nbentry=0; return ''; }
	$redis->multi(Redis::PIPELINE);
	if($preorder !== false) $order($keys);

	$nbok=0;
	if($filter === false)
	{	foreach($keys as $k){ $redis->hgetall($kprefix.$k.$ksuffix); $nbok++;}
		$data = array_combine($keys,$redis->exec());
	}
	else	{	$nb=0;
			$karray = array();
			foreach($keys as $k)
			{	$key = $kprefix.$k.$ksuffix;
				if($filter($k,$key,$nb++,$nbok))
				{ $redis->hgetall($key); $nbok++; $karray[]=$key;
				}
			}
			$data = array_combine($karray,$redis->exec());
		}
	$nbentry=$nbok;

	if($order !== false) $order($data);
	//print '<pre>';print_r($data);print '</pre>';
	return fillDesign($data,$design,$callback);
}

function srandRedisHashFill($setkey, $design, $callback=false, $kprefix='', $ksuffix='', $filter=false)
{	global $redis;
	$k = $redis->sRandMember($setkey); if($k===false) return ''; $key = $kprefix.$k.$ksuffix;
	if($filter !== false) while(!$filter($k,$key)) { $k = $redis->sRandMember($setkey); $key = $kprefix.$k.$ksuffix; }
	return fillDesign(array($key => $redis->hgetall($key)), $design, $callback);
}

function redisHashFill($hashkey, $design, $callback=false)
{	global $redis;
	return fillDesign(array($hashkey => $redis->hgetall($hashkey)), $design, $callback);
}

function isUserPrivilege($prv)
{ return ( isset($_SESSION['user']) && (( isset($_SESSION['user']['right:all']) && !isset($_SESSION['user']['right:'.$prv]) ) || ( isset($_SESSION['user']['right:'.$prv]) &&  $_SESSION['user']['right:'.$prv] )));
}

?>