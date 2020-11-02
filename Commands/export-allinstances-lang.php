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
	, 'class_id:', 'lang:', 'child_class_tag:'
	, 'help', 'debug']);
//print_r($options_array);
if (isset($options_array['help'])) {
	echo 'Export all editora contents to a file or input (json or serialized array)

Parameters:
--from= db4 | db5 (only db4 supported by now)
--dbhost= database host
--dbuser= database user
--dbpass= database password 
--dbname= database name 
--to=file|output
--outputfile=path of the file to export
--outputformat= serialized_array|json 

Others:
--help this help!
--class_id generate only this class_id
--child_class_tag tag of the child class to extract
--lang language of the extraction
--debug show all sqls (if not present false)

example: 
	
1) Export all content of an editora in json format
php export-allinstances-lang.php --class_id=8 --child_class_tag=bloc --lang=es --from=db4 --dbhost=localhost --dbuser=root --dbpass= --dbname=editora_test --to=file --outputformat=json --outputfile=../data/sample-contents.json

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

$to = 'file';
if ($options_array['to'] != 'file') {
	echo "Only --to=file supported by now, use --help for help!\n";
	die;
}

$dbal_config = new \Doctrine\DBAL\Configuration();
if (isset($options_array['debug'])) {
	$dbal_config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
	$params['debug'] = true;
}

$conn_to = null;
if ($options_array['from'] == 'db4' || $options_array['from'] == 'db5') {
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

if (!isset($options_array['class_id'])) {
	die("You must set up a class_id!\n");
} else {
	$class_id=$options_array['class_id'];
}

if (!isset($options_array['lang'])) {
	die("You must set up a lang!\n");
} else {
	$lang=$options_array['lang'];
}





if (!isset($options_array['child_class_tag'])) {
	die("You must set up a child_class_tag!\n");
} else {
	$child_class_tag=$options_array['child_class_tag'];
}


if ($conn_to) {
  $res = array();
	$params['lang']=$lang;
	$params['metadata']=true;
	//$params['debug']=true;
	//$params['showinmediatedebug']=true;

  $extractor = new Extractor($conn_to, $params);
	$res=$extractor->findInstancesInClass($class_id, null, $params, 
	function ($i) use ($extractor, $child_class_tag)
	{
		//echo "$i\n";
		$blocks = $extractor->findChildrenInstances($i, $child_class_tag, null, null);
		return $blocks;
	}	
);

	
	if ($options_array['outputformat'] == 'json') {
		$output = json_encode($res, JSON_PRETTY_PRINT);
	} elseif ($options_array['outputformat'] == 'serialized_array') {
		$output = serialize($res);
	} else {
		die("Unknown output_format, see --help for help. Aborting");
	}

	if ($options_array['to'] == 'output') {
		echo $output;
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