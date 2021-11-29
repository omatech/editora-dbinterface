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
use Omatech\Editora\Generator\ReverseEngineerator;
use Omatech\Editora\Utils\Strings;

ini_set("memory_limit", "5000M");
set_time_limit(0);

$options_array = getopt(null, ['from::', 'to:'
    , 'dbhost:', 'dbuser:', 'dbpass:', 'dbname:'
    , 'outputformat:', 'outputfile:'
    , 'help', 'debug']);
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
--to= file (if not present dumps to standard output)
--outputformat= (array, json, print_r) 
--outputfile= name of the file to export (if not present outputs in the standard output

Others:
--help this help!
--debug (if not present false)

example: 
	
1) Take info from an existing editora and dump array to file
php reverse-engineer-editora.php --from=db4 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=intranetmutua --to=file --outputformat=array --outputfile=../data/reverse_engineer_editora_array.php

2) Take info from an existing editora and dump array to file in json format
php reverse-engineer-editora.php --from=db4 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=intranetmutua --to=file --outputformat=json --outputfile=../data/reverse_engineer_editora.json

3) Take info from an existing editora and dump to the standard output in print_r format
php reverse-engineer-editora.php --from=db4 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=intranetmutua --to=file --outputformat=json --outputfile=../data/reverse_engineer_editora.json

';
    die;
}

if (!isset($options_array['from'])) {
    echo "Missing from or to parameters, use --help for help!\n";
    die;
}

$from_version = 4;
if ($options_array['from'] == 'db5') {
    $from_version = 5;
}

if ($from_version == 5) {
    die("DB5 not supported yet!\n");
}

if (!isset($options_array['outputformat'])) {
    die("Missing --outputformat parameter, use --help for help!\n");
}


$dbal_config = new \Doctrine\DBAL\Configuration();
if (isset($options_array['debug'])) {
    $dbal_config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
    $params['debug'] = true;
}

$conn_from = null;
if ($options_array['from'] == 'db4' || $options_array['from'] == 'db5') {
    $connection_params = array(
        'dbname' => $options_array['dbname'],
        'user' => $options_array['dbuser'],
        'password' => (isset($options_array['dbpass']) ? $options_array['dbpass'] : ''),
        'host' => $options_array['dbhost'],
        'driver' => 'pdo_mysql',
        'charset' => 'utf8'
    );

    $conn_from = \Doctrine\DBAL\DriverManager::getConnection($connection_params, $dbal_config);
}

if ($conn_from) {
    $reverseengineerator = new \Omatech\Editora\Generator\ReverseEngineerator($conn_from, array());
    $data = $reverseengineerator->reverseEngineerEditora();
//echo \Omatech\Editora\Utils\Strings::array2string($data);
    //print_r($data);
    //echo $reverseengineerator->arrayToCode($data);
    //die;
} else {
    die("DB from connection not set, see help for more info\n");
}

//print_r($data);die;


if ($options_array['outputformat'] == 'array') {
    $result = $reverseengineerator->arrayToCode($data);
} elseif ($options_array['outputformat'] == 'json') {
    $result = json_encode($data, JSON_PRETTY_PRINT);
} elseif ($options_array['outputformat'] == 'print_r') {
    $result = print_r($data, true);
} else {
    die("Only array, json or print_r outputformat supported see help for more info\n");
}



if (isset($options_array['outputfile'])) {
    file_put_contents($options_array['outputfile'], $result);
} else {
    echo $result;
}
