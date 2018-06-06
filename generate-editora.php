<?php

$autoload_location = '/vendor/autoload.php';
while (!is_file(__DIR__.$autoload_location)) { $autoload_location='/..'.$autoload_location;}
require_once __DIR__.$autoload_location;

use \Doctrine\DBAL\Configuration;
use \Omatech\Editora\Generator\Generator;

ini_set("memory_limit", "5000M");
set_time_limit(0);

$options_array = getopt(null, ['from::', 'to::'
	, 'dbfromhost:', 'dbfromuser:', 'dbfrompass:', 'dbfromname:'
	, 'inputformat:', 'fromfilename:'
	, 'dbtohost:', 'dbtouser:', 'dbtopass:', 'dbtoname:'
	, 'outputformat:', 'tofilename:'
	, 'help', 'includemetadata']);
//print_r($options_array);
if (isset($options_array['help'])) {
	echo 'Export strings in one language from editora database to excel file or output

From parameters:
--from= file | db4 | db5 (only file supported by now)
--inputformat= array | json (only array supported by now)
--fromfilename= name of the configfile
--dbfromhost= database host
--dbfromuser= database user
--dbfrompass= database password 
--dbfromname= database name 

To parameters:
--to= db4 | file | json | db5 (only supported db4 by now)
--outputformat= (excel, json, array)
--tofilename= name of the file to export
--dbtohost= database host
--dbtouser= database user
--dbtopass= database password 
--dbtoname= database name 

Others:
--help this help!

example: 
	
1) Generate an editora from file
php generate-editora.php --from=file --inputformat=array --fromfilename=intranetmutua.php --to=db4 --dbtohost=localhost --dbtouser=root --dbtopass=xxx --dbtoname=intranetmutua 
';
die;
}

if (!isset($options_array['from']) || !isset($options_array['to'])) {
	echo "Missing from or to parameters, use --help for help!\n";
	die;
}

$from_version = 4;
if ($options_array['from'] == 'db5') {
	$from_version = 5;
}

$dbal_config = new \Doctrine\DBAL\Configuration();

$conn_from = null;
if ($options_array['from'] == 'db4' || $options_array['from'] == 'db5') {
	$connection_params = array(
		'dbname' => $options_array['dbfromname'],
		'user' => $options_array['dbfromuser'],
		'password' => $options_array['dbfrompass'],
		'host' => $options_array['dbfromhost'],
		'driver' => 'pdo_mysql',
		'charset' => 'utf8'
	);

	$conn_from = \Doctrine\DBAL\DriverManager::getConnection($connection_params, $dbal_config);
}

$conn_to = null;
if ($options_array['to'] == 'db4' || $options_array['to'] == 'db5') {
	$connection_params = array(
		'dbname' => $options_array['dbtoname'],
		'user' => $options_array['dbtouser'],
		'password' => $options_array['dbtopass'],
		'host' => $options_array['dbtohost'],
		'driver' => 'pdo_mysql',
		'charset' => 'utf8'
	);

	$conn_to = \Doctrine\DBAL\DriverManager::getConnection($connection_params, $dbal_config);
}


if ($options_array['inputformat']=='array')
{
	if (isset($options_array['inputfile']) && is_file($options_array['inputfile']))
	{
		require_once ($options_array['inputfile']);
	}
	else
	{
		die("Missing inputfile or file not exists see help for more info\n");
	}
}
else
{
	die("Only array inputformat supported see help for more info\n");
}

if ($conn_to)
{
	$generator=new Generator($conn_to, array());
	$generator->createEditora($data);
}
else
{
	die("DB to connection not set, see help for more info\n");
}
