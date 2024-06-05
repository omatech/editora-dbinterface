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

//require_once __DIR__.'/conf/config.php';

use \Doctrine\DBAL\Configuration;
use \Omatech\Editora\Loader\Loader;
use \Omatech\Editora\Clear\Clear;

ini_set("memory_limit", "5000M");
set_time_limit(0);

$options_array = getopt(null, ['from::', 'to::'
	, 'dbhost:', 'dbuser:', 'dbpass:', 'dbname:'
	, 'inputformat:', 'inputfile:'
	, 'help', 'debug', 'delete_previous_data']);
//print_r($options_array);
if (isset($options_array['help'])) {
	echo 'Import all editora contents from a file or input (json or serialized_array)

Parameters:
--from=input|file
--inputformat=serialized_array|json
--inputfile=path of the file to import
--dbhost= database host
--dbuser= database user
--dbpass= database password 
--dbname= database name 
--to=db4 | db5 (only db4 supported by now)

Others:
--help this help!
--debug show all sqls (if not present false)
--delete_previous_data USE WITH CAUTION, if set deletes all the previous data before generating the fake data

example: 
	
1) Import all content of an editora from a json file deleting all the previous data first
php import-content.php --from=file --inputformat=json --inputfile=d:\apons\sample-contents.json --to=db4 --dbhost=localhost --dbuser=root --dbpass= --dbname=editora_test --delete_previous_data 

';
	die;
}

if (!isset($options_array['to'])) {
	echo "Missing TO parameter, use --help for help!\n";
	die;
}

if (!isset($options_array['from'])) {
	echo "Missing FROM parameter, use --help for help!\n";
	die;
}

$to_version = 4;
if ($options_array['to'] != 'db4') {
	echo "Only --to=db4 supported by now, use --help for help!\n";
	die;
}

$from = 'file';
if ($options_array['from'] != 'file') {
	echo "Only --from=file supported by now, use --help for help!\n";
	die;
}



$dbal_config = new \Doctrine\DBAL\Configuration();
if (isset($options_array['debug'])) {
	if (method_exists($dbal_config, 'setSQLLogger')) {
		$dbal_config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
	} else {
		$dbal_config->setMiddlewares([new \Doctrine\DBAL\Logging\Middleware(new \Psr\Log\NullLogger())]);
	}
	$params['debug'] = true;
}

$conn_to = null;
if ($options_array['to'] == 'db4' || $options_array['to'] == 'db5') {
	$connection_params = array(
		'dbname' => $options_array['dbname'],
		'user' => $options_array['dbuser'],
		'password' => (isset($options_array['dbpass']) ? $options_array['dbpass'] : ''),
		'host' => $options_array['dbhost'],
		'driver' => 'pdo_mysql',
		'charset' => 'utf8'
	);

	$conn_to = \Doctrine\DBAL\DriverManager::getConnection($connection_params, $dbal_config);
}

$params = array();


if ($conn_to) {

	if ($options_array['inputformat'] == 'serialized_array' || $options_array['inputformat'] == 'json') {
		if (isset($options_array['inputfile'])) {
			if (is_file($options_array['inputfile'])) {
				if ($options_array['inputformat'] == 'serialized_array') {
					$array_data = file_get_contents($options_array['inputfile']);
					$data = unserialize($array_data);
				} else {// format json
					$json_data = file_get_contents($options_array['inputfile']);
					$data = json_decode($json_data, true);
				}
			} else {
				die("File not found " . $options_array['inputfile']) . " see help for more info\n";
			}
		} else {
			die("Missing inputfile see help for more info\n");
		}
	} else {
		die("Only array inputformat supported see help for more info\n");
	}

	$loader = new Loader($conn_to, $params);

	$loader->startTransaction();
	$start = microtime(true);
	try {


		if (isset($options_array['delete_previous_data'])) {
			echo "\nCleaning all previous content in the database\n";
			$cleaner = new Clear($conn_to, $params);
			$cleaner->deleteAllContent();
		}

		$loader->bulkImportInstances($data['omp_instances']);
		$loader->bulkImportRelationInstances($data['omp_relation_instances']);
		$loader->bulkImportStaticTexts($data['omp_static_text']);
		$loader->bulkImportValues($data['omp_values']);
	} catch (\Exception $e) {
		$loader->rollback();
		echo "Error found: " . $e->getMessage() . "\n";
		echo "Rolling back!!!\n";
		die;
	}
	$loader->commit();
	$end = microtime(true);
	$seconds = round($end - $start, 2);
	echo "\nFinished succesfully in $seconds seconds!\n";
} else {
	die("DB to connection not set, see help for more info\n");
}
