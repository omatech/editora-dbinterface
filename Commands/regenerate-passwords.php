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

$options_array = getopt(null, ['to::'
	, 'dbhost:', 'dbuser:', 'dbpass:', 'dbname:'
	, 'length:'
	, 'help', 'debug']);
//print_r($options_array);
if (isset($options_array['help'])) {
	echo 'Reset all the passwords in the database using a string with uppercase, lowercase, symbols and numbers

Parameters:
--to= db4 | db5 (only db4 supported by now)
--dbhost= database host
--dbuser= database user
--dbpass= database password 
--dbname= database name 
--length= length of the passwords to generate, default 8

Others:
--help this help!
--debug (if not present false)

example: 
	
1) Reset all the passwords of a given database using 10 characters passwords
php regenerate-passwords.php --to=db4 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=intranetmutua --length=10
';
die;
}

if (!isset($options_array['to'])) {
	echo "Missing TO parameter, use --help for help!\n";
	die;
}

$to_version = 4;
if ($options_array['to'] == 'db5') {
	$to_version = 5;
}

if ($to_version!=4){
	echo "Only to=db4 supported by now, use --help for help!\n";
	die;
}

$dbal_config = new \Doctrine\DBAL\Configuration();
if (isset($options_array['debug'])) $dbal_config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

$conn_to = null;
if ($options_array['to'] == 'db4' || $options_array['to'] == 'db5') {
	$connection_params = array(
		'dbname' => $options_array['dbname'],
		'user' => $options_array['dbuser'],
		'password' => $options_array['dbpass'],
		'host' => $options_array['dbhost'],
		'driver' => 'pdo_mysql',
		'charset' => 'utf8'
	);

	$conn_to = \Doctrine\DBAL\DriverManager::getConnection($connection_params, $dbal_config);
}

$length=8;
if ($options_array['length'] && is_numeric($options_array['length'])) $length=$options_array['length'];

if ($conn_to)
{
	$generator=new Generator($conn_to, array());
	$generator->resetPasswords($length);
	$new_passwords=$generator->get_users_passwords();
	foreach ($new_passwords as $user=>$password_array)
	{
		if ($generator->checkPassword($user, $password_array[1]))
		{// El password es igual, quiere decir que lo acabamos de generar correctamente
			echo "Modified user $user with password $password_array[0]\n";
		}
	}
	//print_r($generator->getQueries());
}
else
{
	die("DB to connection not set, see help for more info\n");
}
