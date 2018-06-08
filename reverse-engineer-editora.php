<?php

$autoload_location = '/vendor/autoload.php';
$tries=0;
while (!is_file(__DIR__.$autoload_location)) 
{ 
	$autoload_location='/..'.$autoload_location;
	$tries++;
	if ($tries>10) die("Error trying to find autoload file try to make a composer update first\n");
}
require_once __DIR__.$autoload_location;
//require_once __DIR__.'/conf/config.php';

use \Doctrine\DBAL\Configuration;
use \Omatech\Editora\Generator\Generator;

ini_set("memory_limit", "5000M");
set_time_limit(0);

$options_array = getopt(null, ['from::', 'to::'
	, 'dbfromhost:', 'dbfromuser:', 'dbfrompass:', 'dbfromname:'
	, 'inputformat:', 'inputfile:'
	, 'dbtohost:', 'dbtouser:', 'dbtopass:', 'dbtoname:'
	, 'outputformat:', 'outputfile:'
	, 'help']);
//print_r($options_array);
if (isset($options_array['help'])) {
	echo 'Takes out the editora structure and generates a compatible generator file

From parameters:
--from= db4 | db5 (only db4 supported by now)
--dbhost= database host
--dbuser= database user
--dbpass= database password 
--dbname= database name 

To parameters:
--to= file 
--outputformat= (excel, json, array) (only array and json supported by now)
--outputfile= name of the file to export

Others:
--help this help!

example: 
	
1) Take info from an existing editora and dump array to file
php reverse-engineer-editora.php --from=db4 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=intranetmutua --outputformat=array --outputfile=../sql/reverse_engineer_editora_array.php
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

if ($from_version==5) die ("DB5 not supported yet!\n");

$dbal_config = new \Doctrine\DBAL\Configuration();

$conn_from = null;
if ($options_array['from'] == 'db4' || $options_array['from'] == 'db5') {
	$connection_params = array(
		'dbname' => $options_array['dbname'],
		'user' => $options_array['dbuser'],
		'password' => $options_array['dbpass'],
		'host' => $options_array['dbhost'],
		'driver' => 'pdo_mysql',
		'charset' => 'utf8'
	);

	$conn_from = \Doctrine\DBAL\DriverManager::getConnection($connection_params, $dbal_config);
}


if ($options_array['inputformat']=='array')
{
	if (isset($options_array['inputfile']))
	{
		if (is_file($options_array['inputfile']))
		{
			require_once ($options_array['inputfile']);
		}
		else
		{
			die("File not found ".$options_array['inputfile'])." see help for more info\n";
		}
	}
	else
	{
		die("Missing inputfile see help for more info\n");
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
	$data=$generator->getFinalData();
	$new_passwords=$generator->get_users_passwords();
	foreach ($new_passwords as $user=>$password_array)
	{
		if ($generator->checkPassword($user, $password_array[1]))
		{// El password es igual, quiere decir que lo acabamos de generar correctamente
			echo "New user: $user with password $password_array[0]\n";
		}
	}
	
	//print_r($generator->getQueries());
}
else
{
	die("DB to connection not set, see help for more info\n");
}
