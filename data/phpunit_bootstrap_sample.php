<?php

define('DBHOST', 'localhost');
define('DBUSER', 'root');
define('DBPASS', '');
define('DBNAME', 'editora_test');
define('DEBUG', false);


error_reporting(E_ALL);
ini_set('display_errors', '1');
//echo __DIR__."\n";die;

$autoload_location = '/vendor/autoload.php';
$tries = 0;
while (!is_file(__DIR__ . $autoload_location)) {
	$autoload_location = '/..' . $autoload_location;
	$tries++;
	if ($tries > 10)
		die("Error trying to find autoload file try to make a composer update first\n");
}
require_once __DIR__ . $autoload_location;

$dbal_config = new \Doctrine\DBAL\Configuration();
if (DEBUG)
	if (method_exists($dbal_config, 'setSQLLogger')) {
		$dbal_config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
	} else {
		$dbal_config->setMiddlewares([new \Doctrine\DBAL\Logging\Middleware(new \Psr\Log\NullLogger())]);
	}
$connection_params = array(
	'dbname' => DBNAME,
	'user' => DBUSER,
	'password' => DBPASS,
	'host' => DBHOST,
	'driver' => 'pdo_mysql',
	'charset' => 'utf8'
);

$conn = \Doctrine\DBAL\DriverManager::getConnection($connection_params, $dbal_config);

class PHPUnit_Bootstrap_Sample {


}
