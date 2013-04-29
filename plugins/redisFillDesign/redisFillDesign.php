<?php

// an empty function with plugin name, needed by plugin system.
function fn_redisFillDesign($args)
{	// in future, will give access of all redis hash fill from template system,
	// ie : §redisFillDesign|hashFillMethod|design|dataKey§
}

/* lredisHashFill

	will get a redis list of hash keys, and fill a design with these data
	kprefix and ksuffix are the start and end of hash key name to recreate them.
	callback will be used to manipulate data between retrieve and fill (see fillDesign core function)
*/

function lredisHashFill($listkey, $design, $callback=false, $kprefix='', $ksuffix='', $start=0, $end=-1, &$size=0)
{	global $redis;
	list($keys,$size) = $redis->multi(Redis::PIPELINE)->lRange($listkey,$start,$end)->lSize($listkey)->exec();
	if(empty($keys)) return '';
	$redis->multi(Redis::PIPELINE); foreach($keys as $k) $redis->hgetall($kprefix.$k.$ksuffix);
	$data = array_combine($keys,$redis->exec());
	return fillDesign($data,$design,$callback);
}

/* sredisHashFill

	same principle, will fill design with redis hash keys taked this time from a redis set,
	here a filter function can be provided, who need to return 0 or 1 and get the hash key name, to skip some unneeded data,
	also, preorder function, if set, will order the set key data, this function must be a php one, by exemple, "shuffle"
	order function do the same but with data and not key name.
	nbentry is here to limit the number of design filled in one time by this method
*/

function sredisHashFill($setkey, $design, $callback=false, $kprefix='', $ksuffix='', $filter=false, $order=false, $preorder=false,&$nbentry=0)
{	global $redis; $keys = $redis->smembers($setkey); if(empty($keys)){ $nbentry=0; return ''; }
	$redis->multi(Redis::PIPELINE);
	if($preorder !== false) $preorder($keys);

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
	return fillDesign($data,$design,$callback);
}

/* srandRedisHashFill

	will take a random hash key from a redis set, optionally filter it and return a filled design with this hash key data
*/

function srandRedisHashFill($setkey, $design, $callback=false, $kprefix='', $ksuffix='', $filter=false)
{	global $redis;
	$k = $redis->sRandMember($setkey); if($k===false) return ''; $key = $kprefix.$k.$ksuffix;
	if($filter !== false) while(!$filter($k,$key)) { $k = $redis->sRandMember($setkey); $key = $kprefix.$k.$ksuffix; }
	return fillDesign(array($key => $redis->hgetall($key)), $design, $callback);
}

/* redisHashFill

	direct fill design from a hash key
*/

function redisHashFill($hashkey, $design, $callback=false)
{	global $redis;
	return fillDesign(array($hashkey => $redis->hgetall($hashkey)), $design, $callback);
}

?>