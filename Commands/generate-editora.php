<?php

$autoload_location = '/vendor/autoload.php';
$tries = 0;
while (!is_file(__DIR__ . $autoload_location)) {
    $autoload_location = '/..' . $autoload_location;
    $tries++;
    if ($tries > 10) {
        die("Error trying to find autoload file try to make a composer update first\n");
    }
}
require_once __DIR__ . $autoload_location;

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
    , 'help', 'debug']);
//print_r($options_array);
if (isset($options_array['help'])) {
    echo 'Generate editora structure from an array or json file

From parameters:
--from= file | db4 | db5 (only file supported by now)
--inputformat= array | json (only array supported by now)
--inputfile= name of the configfile
--dbfromhost= database host
--dbfromuser= database user
--dbfrompass= database password 
--dbfromname= database name 

To parameters:
--to= db4 | file | json | db5 (only supported db4 by now)
--outputformat= (excel, json, array)
--outputfile= name of the file to export
--dbtohost= database host
--dbtouser= database user
--dbtopass= database password 
--dbtoname= database name 

Others:
--help this help!
--debug (if not present false)

example: 
	
1) Generate an editora from file
php generate-editora.php --from=file --inputformat=array --inputfile=../data/sample_editora_array.php --to=db4 --dbtohost=localhost --dbtouser=root --dbtopass=xxx --dbtoname=intranetmutua 
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
if (isset($options_array['debug'])) {
    $dbal_config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
    $params['debug'] = true;
}

$conn_to = null;
if ($options_array['from'] == 'db4' || $options_array['from'] == 'db5') {
    $connection_params = array(
        'dbname' => $options_array['dbfromname'],
        'user' => $options_array['dbfromuser'],
        'password' => $options_array['dbfrompass'],
        'host' => $options_array['dbfromhost'],
        'driver' => 'pdo_mysql',
        'charset' => 'utf8'
    );

    $conn_to = \Doctrine\DBAL\DriverManager::getConnection($connection_params, $dbal_config);
}

$conn_to = null;
if ($options_array['to'] == 'db4' || $options_array['to'] == 'db5') {
    $connection_params = array(
        'dbname' => $options_array['dbtoname'],
        'user' => $options_array['dbtouser'],
        'password' => (isset($options_array['dbtopass']) ? $options_array['dbtopass'] : ''),
        'host' => $options_array['dbtohost'],
        'driver' => 'pdo_mysql',
        'charset' => 'utf8'
    );

    $conn_to = \Doctrine\DBAL\DriverManager::getConnection($connection_params, $dbal_config);
}


if ($options_array['inputformat'] == 'array' || $options_array['inputformat'] == 'json') {
    if (isset($options_array['inputfile'])) {
        if (is_file($options_array['inputfile'])) {
            if ($options_array['inputformat'] == 'array') {
                require_once($options_array['inputfile']);
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

if ($conn_to) {
    $generator = new Generator($conn_to, array());

    $generator->startTransaction();
    $start = microtime(true);
    try {
        $generator->createEditora($data);
        $data = $generator->getFinalData();
        $new_passwords = $generator->get_users_passwords();
        if ($new_passwords) {
            foreach ($new_passwords as $user => $password_array) {
                if ($generator->checkPassword($user, $password_array[1])) {// El password es igual, quiere decir que lo acabamos de generar correctamente
                    echo "New user: $user with password $password_array[0]\n";
                }
            }
        }
    } catch (\Exception $e) {
        $generator->rollback();
        echo "Error found: " . $e->getMessage() . "\n";
        echo "Rolling back!!!\n";
        die;
    }
    $generator->commit();
    $end = microtime(true);
    $seconds = round($end - $start, 2);
    echo "\nFinished succesfully in $seconds seconds!\n";


//print_r($generator->getQueries());
} else {
    die("DB to connection not set, see help for more info\n");
}
