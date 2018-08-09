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
use \Omatech\Editora\Loader\Loader;
use \Omatech\Editora\Clear\Clear;

ini_set("memory_limit", "5000M");
set_time_limit(0);

$options_array = getopt(null, ['to::', 'batch_id::'
    , 'dbhost:', 'dbuser:', 'dbpass:', 'dbname:'
    , 'help', 'debug', 'delete_previous_data']);
//print_r($options_array);
if (isset($options_array['help'])) {
    echo 'Remove all content with a given batch_id or all content except Global and Home if delete_previous_data flag is present

Parameters:
--to= db4 | db5 (only db4 supported by now)
--batch_id= ID of the batch content to remove
--dbhost= database host
--dbuser= database user
--dbpass= database password 
--dbname= database name 

Others:
--help this help!
--debug show all sqls (if not present false)
--delete_previous_data USE WITH CAUTION, if set deletes all the previous data before generating the fake data

example: 
	
1) Remove fake content from editora with batch_id=76767
php remove-content.php --to=db4 --batch_id=76767 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=intranetmutua 

2) Remove ALL the content of the editora
php remove-content.php --to=db4 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=intranetmutua --delete_previous_data
';
    die;
}

if (!isset($options_array['to'])) {
    echo "Missing TO parameter, use --help for help!\n";
    die;
}

if (!isset($options_array['batch_id']) && !isset($options_array['delete_previous_data'])) {
    echo "Missing BATCH_ID parameter or DELETE_PREVIOUS_DATA, use --help for help!\n";
    die;
}

$batch_id=false;
if (isset($options_array['batch_id']))
{
	$batch_id=$options_array['batch_id'];
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
if (isset($options_array['debug'])) 
{
	$dbal_config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
	$params['debug']=true;
}

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

if ($conn_to)
{
	if ($batch_id)
	{
		echo "\nCleaning BATCH=$batch_id\n";
    $loader=new Loader($conn_to);
		$loader->delete_instances_in_batch($batch_id);
	}
	else
	{
		if (isset($options_array['delete_previous_data']))
		{
			echo "\nCleaning all previous content in the database\n";
			$cleaner=new Clear($conn_to, $params);
			$cleaner->deleteAllContentExceptHomeAndGlobal();
		}
		else
		{
			echo "\nWeird! we have not batch ID and neither delete_previous_data flag, no action taken!\n";
		}
	}
  echo "\n\nFinish!\n";
}
else
{
    die("DB to connection not set, see help for more info\n");
}