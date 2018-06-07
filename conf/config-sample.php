<?php
//à

if ((strpos($_SERVER['HTTP_HOST'],'oma.lan')!==false || strpos($_SERVER['HTTP_HOST'],'devel')!==false)) 
{// entorn de devel
	echo "ENTORN DE DEVEL!!!!\n";
	define("dbhost","localhost");
	define("dbuser","");
	define("dbpass",'');
	define("dbname","");            
}
elseif ((strpos($_SERVER['HTTP_HOST'],'.omatech.com')!==false || strpos($_SERVER['HTTP_HOST'],'preprod')!==false)) 
{// entorn de preprod al mateix server que prod
	echo "ENTORN DE DEVEL (PREPROD)!!!!\n";
	define("dbhost","localhost");
	define("dbuser","");
	define("dbpass",'');
	define("dbname","");              
}
else
{// PROD
	define("dbhost","localhost");
	define("dbuser","");
	define("dbpass",'');
	define("dbname","");
}

// For geocode in Loader
//$json=file_get_contents('http://xxx.com/geocoderhosts.php');
//$available_hosts=json_decode($json);
