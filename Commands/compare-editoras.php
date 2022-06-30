<?php

$autoload_location = '/vendor/autoload.php';
$tries = 0;
while (!is_file(__DIR__ . $autoload_location)) {
	$autoload_location = '/..' . $autoload_location;
	$tries++;
	if ($tries > 10)
		die("Error trying to find autoload file try to make a composer update first\n");
}
require_once __DIR__ . $autoload_location;

use \Doctrine\DBAL\Configuration;

ini_set("memory_limit", "5000M");
set_time_limit(0);

$options_array = getopt(null, ['dbpass:', 'dbfromname:', 'dbtoname:'
	, 'help']);
//print_r($options_array);
if (isset($options_array['help'])) {
	echo 'Use compare_editoras.php to compare two editoras in different databases, useful to test reverse engineered editoras, for example.
		
Only works in localhost! and with dbuser root only db4 format is supported

General parameters:
--dbpass= database password for both databases

From parameters:
--dbfromname= database name from

To parameters:
--dbtoname= database name to

Others:
--help this help!

example: 
	
1) compare two databases
php compare-editoras.php --dbpass=xxx --dbfromname=editora1 --dbtoname=editora2
';

die;
}

$to_version = 4;
$from_version = 4;

$dbal_config = new \Doctrine\DBAL\Configuration();

$connection_params_from = array(
  'dbname' => $options_array['dbfromname'],
  'user' => 'root',
  'password' => (isset($options_array['dbpass']) ? $options_array['dbpass'] : ''),
  'host' => 'localhost',
  'driver' => 'pdo_mysql',
  'charset' => 'utf8'
);
$conn_from = \Doctrine\DBAL\DriverManager::getConnection($connection_params_from, $dbal_config);


$connection_params_to = array(
  'dbname' => $options_array['dbtoname'],
  'user' => 'root',
  'password' => (isset($options_array['dbpass']) ? $options_array['dbpass'] : ''),
  'host' => 'localhost',
  'driver' => 'pdo_mysql',
  'charset' => 'utf8'
);
$conn_to = \Doctrine\DBAL\DriverManager::getConnection($connection_params_to, $dbal_config);


$params = [
	'default_language' => 'es'
	, 'to_version' => $to_version
	, 'from_version' => $from_version
	, 'from_dbname' => $options_array['dbfromname']
	, 'to_dbname' => $options_array['dbtoname']
];

$model = new \Omatech\Editora\Comparator\Comparator($conn_from, $conn_to, $params, true);
$model->compare();








