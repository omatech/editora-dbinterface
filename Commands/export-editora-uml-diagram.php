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
use \Omatech\Editora\Extractor\Extractor;

ini_set("memory_limit", "5000M");
set_time_limit(0);

$options_array = getopt(null, ['from::', 'to::'
	, 'dbhost:', 'dbuser:', 'dbpass:', 'dbname:'
	, 'outputformat:', 'outputfile:'
	, 'lang:', 'help', 'debug']);
//print_r($options_array);
if (isset($options_array['help'])) {
	echo 'Export all editora class structure to a file or output (json or plantuml text or svg)

Parameters:
--from= db4 | db5 (only db4 supported by now)
--dbhost= database host
--dbuser= database user
--dbpass= database password 
--dbname= database name 
--to=file|output
--outputfile=path of the file to export
--outputformat= plantuml|json 

Others:
--help this help!
--lang language of the extraction
--debug show all sqls (if not present false)

example: 
	
1) Export all structure of an editora in plantuml format to a file
php export-editora-uml-diagram.php --lang=es --from=db4 --dbhost=localhost --dbuser=root --dbpass= --dbname=editora_test --to=file --outputformat=plantuml --outputfile=../data/sample-contents.uml

2) Export all structure of an editora in plantuml format to the output
php export-editora-uml-diagram.php --lang=es --from=db4 --dbhost=localhost --dbuser=root --dbpass= --dbname=editora_test --to=output --outputformat=plantuml 
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

$from_version = 4;
if ($options_array['from'] != 'db4') {
	echo "Only --from=db4 supported by now, use --help for help!\n";
	die;
}

$to=$options_array['to'];
if ($options_array['to'] != 'file' && $options_array['to'] != 'output') {
	echo "Only --to=file or --to=output supported by now, use --help for help!\n";
	die;
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

$params = array();

if (!isset($options_array['lang'])) {
	die("You must set up a lang!\n");
} else {
	$lang=$options_array['lang'];
}


if ($conn_from) {
  $res = array();

  $extractor = new Extractor($conn_from, $params);
  $res=$extractor->getUML();
  $output='';
	
	if ($options_array['outputformat'] == 'json') {
		$output = json_encode($res, JSON_PRETTY_PRINT);
	} elseif ($options_array['outputformat'] == 'plantuml') {
    foreach ($res as $line)
    {
      $output.=$line['statement']."\n";
    }
	} else {
		die("Unknown output_format, see --help for help. Aborting");
	}


  if ($options_array['to'] == 'output') {
		echo $output; die;
	} elseif ($options_array['to'] == 'file') {
		if (isset($options_array['outputfile'])) {
			file_put_contents($options_array['outputfile'], $output);
		} else {
			die("You must especify a valid filename with outputfile parameter when outputing to file. Aborting\n");
		}
	} else {
		die('Unknown to parameter, aborting!\n');
	}

	echo "\n\nFinish!\n";
} else {
	die("DB to connection not set, see help for more info\n");
}