<?php

$autoload_location = '/vendor/autoload.php';
while (!is_file(__DIR__ . $autoload_location)) {
	$autoload_location = '/..' . $autoload_location;
}
require_once __DIR__ . $autoload_location;

$usleep_pause = 500000;
$price_per_character = 0.002;

use \Doctrine\DBAL\Configuration;
use \Omatech\Translator\TranslatorModel;
# Imports the Google Cloud client library
use Google\Cloud\Translate\TranslateClient;

ini_set("memory_limit", "5000M");
set_time_limit(0);

$options_array = getopt(null, ['from::', 'to::'
	, 'dbfromhost:', 'dbfromuser:', 'dbfrompass:', 'dbfromname:', 'sourcelanguage:'
	, 'dbtohost:', 'dbtouser:', 'dbtopass:', 'dbtoname:', 'destinationlanguage:'
	, 'help', 'googlecloudprojectid:', 'costestimationonly']);
//print_r($options_array);
if (isset($options_array['help'])) {
	echo 'Translate missing strings from a source language to a destination language, using direct database connection

From parameters:
--from= db4 | db5 
--dbfromuser= database user from
--dbfrompass= database password from
--dbfromhost= database host from
--dbfromname= database name from
--sourcelanguage= Source Language (ca|es|en...)

To parameters:
--to= db5 
--dbtouser= database user to
--dbtopass= database password to
--dbtohost= database host to
--dbtoname= database name to
--destinationlanguage= Destination Language (ca|es|en...)

Others:
--help this help!
--googlecloudprojectid= ID of your project, billing must be set and authorized in the running host
--costestimationonly If present only calculates the cost of the missing charaters to translate

example: 
	
1) Translate texts that exists in english but not in spanish using google translate
php translate.php --sourcelanguage=en --from=db5 --dbfromhost=localhost --dbfromuser=root --dbfrompass=xxx --dbfromname=panreac5 --to=db5 --destinationlanguage=es --dbtohost=localhost --dbtouser=root --dbtopass=xxx --dbtoname=panreac5 --googlecloudprojectid=yyy

2) Estimates the cost to translate missing words in German that exists in english but using google translate
php translate.php --costestimationonly --sourcelanguage=en --from=db5 --dbfromhost=localhost --dbfromuser=root --dbfrompass=xxx --dbfromname=panreac5 --to=db5 --destinationlanguage=de --dbtohost=localhost --dbtouser=root --dbtopass=xxx --dbtoname=panreac5 --googlecloudprojectid=yyy

';
	die;
}

if (!isset($options_array['from']) || !isset($options_array['to'])) {
	echo "Missing from or to parameters, use --help for help!\n";
	die;
}

$to_version = 4;
if ($options_array['to'] == 'editora5file' || $options_array['to'] == 'editora5minimalfile' || $options_array['to'] == 'db5' || $options_array['to'] == 'editora5generatorfile') {
	$to_version = 5;
}

$from_version = 4;
if ($options_array['from'] == 'db5' || $options_array['from'] == 'editora5file' || $options_array['from'] == 'editora5minimalfile' || $options_array['to'] == 'editora5generatorfile') {
	$from_version = 5;
}


$dbal_config = new \Doctrine\DBAL\Configuration();

$conn_from = null;
if ($options_array['from'] == 'db4' || $options_array['from'] == 'db5') {
	$connection_params_from = array(
		'dbname' => $options_array['dbfromname'],
		'user' => $options_array['dbfromuser'],
		'password' => $options_array['dbfrompass'],
		'host' => $options_array['dbfromhost'],
		'driver' => 'pdo_mysql',
		'charset' => 'utf8'
	);

	$conn_from = \Doctrine\DBAL\DriverManager::getConnection($connection_params_from, $dbal_config);
}

$conn_to = null;
if ($options_array['to'] == 'db4' || $options_array['to'] == 'db5') {
	$connection_params_to = array(
		'dbname' => $options_array['dbtoname'],
		'user' => $options_array['dbtouser'],
		'password' => $options_array['dbtopass'],
		'host' => $options_array['dbtohost'],
		'driver' => 'pdo_mysql',
		'charset' => 'utf8'
	);

	$conn_to = \Doctrine\DBAL\DriverManager::getConnection($connection_params_to, $dbal_config);
}

$params = [
	'destination_language' => $options_array['destinationlanguage']
	, 'source_language' => $options_array['sourcelanguage']
	, 'from_version' => $from_version
	, 'to_version' => $to_version
];

$result = array();

unset($options_array['dbpass']);

$model = new \Omatech\Translator\TranslatorModel($conn_from, $conn_to, $params, false);

$rows = $model->get_missing_destination_texts('conn_from');

foreach ($rows['values'] as $val) {
	$result['data'][] = ['key1' => $val['inst_id'], 'key2' => $val['atri_id'], 'value' => $val['value']];
}

foreach ($rows['statics'] as $val) {
	$result['data'][] = ['key1' => 'statics', 'key2' => $val['key'], 'value' => $val['value']];
}

foreach ($rows['niceurls'] as $val) {
	$result['data'][] = ['key1' => 'niceurls', 'key2' => $val['inst_id'], 'value' => $val['value']];
}

$rows = $result['data'];
$instances_modified = array();
$nices = $values = $statics = 0;
$charsnices = $charsvalues = $charsstatics = 0;
$model->start_transaction();

# Instantiates a google translate client
$translate = new TranslateClient([
	'projectId' => $options_array['googlecloudprojectid']
	]);

if (isset($options_array['costestimationonly'])) {
	foreach ($rows as $row) {
		if ($row['key1'] == 'niceurls') {
			echo "n";
			$charsnices += strlen($row['value']);
			if (!in_array($row['key2'], $instances_modified)) {
				$instances_modified[] = $row['key2'];
			}
			$nices++;
		} elseif ($row['key1'] == 'statics') {
			echo "s";
			$charsstatics += strlen($row['value']);
			$statics++;
		} else {
			echo "v";
			$charsvalues += strlen($row['value']);
			if (!in_array($row['key1'], $instances_modified)) {
				$instances_modified[] = $row['key1'];
			}
			$values++;
		}
	}
	echo "\n";
	// TBD inst_ids para eliminar la cache
	echo "$nices nice url records present with $charsnices characters!\n";
	echo "$statics static records present with $charsstatics characters!\n";
	echo "$values values records present with $charsvalues characters!\n";
	$total_rows = $nices + $statics + $values;
	$total_chars = $charsnices + $charsstatics + $charsvalues;
	echo "Total characters: $total_chars\n";
	echo "Estimated cost " . round($total_chars * $price_per_character, 2) . " euros\n";
	echo "Estimated time " . ($total_rows) . " seconds (" . round($total_rows / 3600, 2) . ") hours\n";
} else {// Translate de verdad
	foreach ($rows as $row) {
		if ($row['key1'] == 'niceurls') {
			echo "n";

			$translation = $translate->translate($row['value'], ['source' => $options_array['sourcelanguage'], 'target' => $options_array['destinationlanguage']]);
			$translated_value = $translation['text'];

			$model->set_niceurl($row['key2'], $translated_value);
			if (!in_array($row['key2'], $instances_modified)) {
				$instances_modified[] = $row['key2'];
			}
			$nices++;
		} elseif ($row['key1'] == 'statics') {
			echo "s";
			$translation = $translate->translate($row['value'], ['source' => $options_array['sourcelanguage'], 'target' => $options_array['destinationlanguage']]);
			$translated_value = $translation['text'];

			$model->set_static($row['key2'], $translated_value);
			$statics++;
		} else {
			echo "v";
			//echo " inst_id=".$row['key1']." atri_id=".$row['key2']."\n";
			$translation = $translate->translate($row['value'], ['source' => $options_array['sourcelanguage'], 'target' => $options_array['destinationlanguage']]);
			$translated_value = $translation['text'];

			$model->set_value($row['key1'], $row['key2'], $translated_value);
			if (!in_array($row['key1'], $instances_modified)) {
				$instances_modified[] = $row['key1'];
			}
			$values++;
		}
		usleep($usleep_pause);
	}

	echo "\nUpdating instance update date\n";
	foreach ($instances_modified as $inst_id) {
		if ($to_version == 4) {
			$model->update_instance4($inst_id);
		} else {
			$model->update_instance5($inst_id);
		}
		echo ".";
	}
	echo "\nDone!\n";
	$model->commit();

	echo "\n";
	// TBD inst_ids para eliminar la cache
	echo "$nices nice urls translated!\n";
	echo "$statics static texts translated!\n";
	echo "$values values translated!\n";
}
echo count($instances_modified) . " instances modified!\n";
$model->set_update_date_in_instances($instances_modified);
