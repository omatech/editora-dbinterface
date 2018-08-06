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
use \Omatech\Editora\FakeContent\FakeContent;

ini_set("memory_limit", "5000M");
set_time_limit(0);

$options_array = getopt(null, ['to::'
    , 'dbhost:', 'dbuser:', 'dbpass:', 'dbname:'
		, 'num_instances:', 'include_classes:', 'exclude_classes:'
    , 'help', 'debug']);
//print_r($options_array);
if (isset($options_array['help'])) {
    echo 'Modernize editora DB to include latest changes in DB structure

Parameters:
--to= db4 | db5 (only db4 supported by now)
--dbhost= database host
--dbuser= database user
--dbpass= database password 
--dbname= database name 

Others:
--help this help!
--num_instances number of instance to create for each class
--include_classes generate only this class_ids, comma separated
--exclude_classes generate all but this class_ids, comma separated
--debug show all sqls (if not present false)

example: 
	
1) Generate fake content for editora 4 instances for each class by default
php fake-content.php --to=db4 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=intranetmutua 

2) Generate fake content for editora 50 instances for each class 
php fake-content.php --to=db4 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=intranetmutua --num_instances=50

3) Generate fake content for editora 2 instances only for classes 20 and 21
php fake-content.php --to=db4 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=intranetmutua --num_instances=2 --include_classes=20,21

4) Generate fake content for editora 2 instances for all classes except 20 and 21
php fake-content.php --to=db4 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=intranetmutua --num_instances=2 --exclude_classes=20,21
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

$params=array();

if (isset($options_array['num_instances']))
{
	$params['num_instances']=$options_array['num_instances'];
}

if (isset($options_array['include_classes']))
{
	$params['include_classes']=$options_array['include_classes'];
}

if (isset($options_array['exclude_classes']))
{
	$params['exclude_classes']=$options_array['exclude_classes'];
}

if ($conn_to)
{

    $fakecontent=new FakeContent($conn_to, $params);
    $fakecontent->createContentEditora($conn_to);

    echo "\n\nFinish!\n";
}
else
{
    die("DB to connection not set, see help for more info\n");
}