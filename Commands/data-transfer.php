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

$options_array = getopt(null, ['from::', 'to::'
	, 'dbpass:', 'dbfromname:', 'dbtoname:'
	, 'help']);
//print_r($options_array);
if (isset($options_array['help'])) {
	echo 'Use data-transfer.php to extract editora4 information and get it into editora5 format
		
Only works in localhost! and with dbuser root

From parameters:
--from= db4 | db5 
--dbpass= database password from
--dbfromname= database name from

To parameters:
--to= db5
--dbtoname= database name to

Others:
--help this help!

example: 
	
1) move data from a db4 to a db5 editora
php data-transfer.php --from=db4 --to=db5 --dbpass=xxx --dbfromname=panreac --dbtoname=panreac5
';
	die;
}

if (!isset($options_array['from']) || !isset($options_array['to'])) {
	echo "Missing from or to parameters, use --help for help!\n";
	die;
}

$to_version = 4;
if ($options_array['to'] == 'db5') {
	$to_version = 5;
}

$from_version = 4;
if ($options_array['from'] == 'db5') {
	$from_version = 5;
}

$dbal_config = new \Doctrine\DBAL\Configuration();

$conn_to = null;
if ($options_array['from'] == 'db4' || $options_array['from'] == 'db5') {
	$connection_params_from = array(
		'dbname' => $options_array['dbfromname'],
		'user' => 'root',
		'password' => (isset($options_array['dbpass']) ? $options_array['dbpass'] : ''),
		'host' => 'localhost',
		'driver' => 'pdo_mysql',
		'charset' => 'utf8'
	);

	$conn_to = \Doctrine\DBAL\DriverManager::getConnection($connection_params_from, $dbal_config);
}


$params = [
	'default_language' => 'es'
	, 'to_version' => $to_version
	, 'from_version' => $from_version
	, 'from_dbname' => $options_array['dbfromname']
	, 'to_dbname' => $options_array['dbtoname']
];

$result = array();

unset($options_array['dbpass']);
$result['metadata']['options'] = $options_array;
$result['metadata']['params'] = $params;
$result['metadata']['generated_at'] = time();
$result['metadata']['generated_at_human'] = date('Y-m-d H:i:s');

$model = new \Omatech\Editora\Migrator\Migrator($conn_to, null, $params, false);

if ($options_array['from'] == 'db4' && $options_array['to'] == 'db5') {
	$model->start_transaction();
	$model->transfer_data_from4_to5();
	$model->commit();
}








