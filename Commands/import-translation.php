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
use \Omatech\Editora\Translator\TranslatorModel;
use \PhpOffice\PhpSpreadsheet\Spreadsheet;
use \PhpOffice\PhpSpreadsheet\IOFactory;

ini_set("memory_limit", "5000M");
set_time_limit(0);

$options_array = getopt(null, ['from::', 'to::'
	, 'dbhost:', 'dbuser:', 'dbpass:', 'dbname:', 'sourcelanguage:', 'destinationlanguage:'
	, 'inputformat:', 'fromfilename:', 'offsetlang:', 'debug'
	, 'help']);
//print_r($options_array);
if (isset($options_array['help'])) {
	echo 'Import strings from an excel, json file or input to editora database

From parameters:
--from= input, file 
--inputformat= (excel, json)
--fromfilename= name of the file to import

To parameters:
--to= db4 | db5
--dbhost= database host
--dbuser= database user
--dbpass= database password 
--dbname= database name
--destinationlanguage= Destination Language (ca|es|en...)

Others:
--help this help!
--offsetlang (default 10000)
--debug (if not present false)

example: 
	
1) Import texts in spanish (from english) to an editora version 5 from an excel file
php import-translation.php --sourcelanguage=en --from=file --inputformat=excel --fromfilename=missing_translation_from_en_to_es_panreac.xlsx --to=db5 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=panreac5  --destinationlanguage=es

2) Import texts in spanish to an editora version 5 from a json file
php import-translation.php --sourcelanguage=en --from=file --inputformat=json --fromfilename=missing_translation_from_en_to_es_panreac.json --to=db5 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=panreac5  --destinationlanguage=es

3) Import texts in spanish to an editora version 5 from a json input
php import-translation.php --sourcelanguage=en --from=input --inputformat=json --to=db5 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=panreac5  --destinationlanguage=es < missing_translation_from_en_to_es_panreac.json

4) Pipe an export and an import from the missing spanish texts and fill them with it\'s english version using json
php export-translation.php --sourcelanguage=en --from=db5 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=panreac5 --to=output --outputformat=json --destinationlanguage=es --what=missing | php import-translation.php --from=input --inputformat=json --to=db5 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=panreac5 --sourcelanguage=en --destinationlanguage=es 

';
	die;
}

//print_r($options_array);
if (!isset($options_array['from']) || !isset($options_array['to'])) {
	echo "Missing from or to parameters, use --help for help!\n";
	die;
}

$to_version = 4;
if ($options_array['to'] == 'db5') {
	$to_version = 5;
}

$dbal_config = new \Doctrine\DBAL\Configuration();
if (isset($options_array['debug'])) {
	$dbal_config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
	$params['debug'] = true;
}

$conn_to = null;
if ($options_array['to'] == 'db4' || $options_array['to'] == 'db5') {
	$connection_params = array(
		'dbname' => $options_array['dbname'],
		'user' => $options_array['dbuser'],
		'password' => (isset($options_array['dbpass']) ? $options_array['dbpass'] : ''),
		'host' => $options_array['dbhost'],
		'driver' => 'pdo_mysql',
		'charset' => 'utf8'
	);

	$conn = \Doctrine\DBAL\DriverManager::getConnection($connection_params, $dbal_config);
}

$offsetlang = 10000;
if (isset($options_array['offsetlang']))
	$offsetlang = $options_array['offsetlang'];

$params = [
	'destination_language' => $options_array['destinationlanguage']
	, 'source_language' => $options_array['sourcelanguage']
	, 'to_version' => $to_version
	, 'offsetlang' => $offsetlang
];

$result = array();


$model = new TranslatorModel($conn, $conn, $params, false);


if ($options_array['from'] == 'input') {
	$input = file_get_contents('php://stdin');
} elseif ($options_array['from'] == 'file') {
	$input = file_get_contents($options_array['fromfilename']);
} else {
	die('Unknown from parameter, aborting!\n');
}

if ($options_array['inputformat'] == 'json') {
	$result = json_decode($input, true);
} elseif ($options_array['inputformat'] == 'excel') {
	$temp_filename = tempnam(sys_get_temp_dir(), 'tmp');
	file_put_contents($temp_filename, $input);
	$objSpreadsheet = IOFactory::load($temp_filename);
	//  Get worksheet dimensions
	$sheet = $objSpreadsheet->getSheet(0);
	$highestRow = $sheet->getHighestRow();
	$highestColumn = $sheet->getHighestColumn();

	for ($row = 2; $row <= $highestRow; $row++) {
		$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
		$final_row = $rowData[0];

		$element = array();
		$element['key1'] = $final_row[0];
		$element['key2'] = $final_row[1];
		$element['value'] = $final_row[2];

		if ($element['key1'] != '' && $element['key2'] != '' && $element['value'] != '')
			$result[] = $element;
	}

	//TBD
}


if (isset($result['metadata'])) {
	$rows = $result['data'];
	echo "Importing:\n";
	print_r($result['metadata']);
} else {
	$rows = $result;
}


//print_r($rows);die;



if ($options_array['to'] == 'db4' || $options_array['to'] == 'db5') {// let's backup the database first
	$backup_date = date('Ymd_His');
	$cmd = "mysqldump --user=" . $options_array['dbuser'] . " --password=" . $options_array['dbpass'] . " --host=" . $options_array['dbhost'] . " " . $options_array['dbname'] . " omp_values > " . sys_get_temp_dir() . "/backup_ompvalues_preimport_" . $options_array['dbname'] . "_$backup_date.sql";
	echo "Performing database backup $backup_date\n";
	echo "$cmd\n";
	exec($cmd);
	echo "Finishing database backup!\n";

	$connection_params = array(
		'dbname' => $options_array['dbname'],
		'user' => $options_array['dbuser'],
		'password' => $options_array['dbpass'],
		'host' => $options_array['dbhost'],
		'driver' => 'pdo_mysql',
		'charset' => 'utf8'
	);

	$conn = \Doctrine\DBAL\DriverManager::getConnection($connection_params, $dbal_config);
}

unset($options_array['dbpass']);

//print_r($rows);die;

$instances_modified = array();
$nices = $values = $statics = 0;


$model->start_transaction();
$start = microtime(true);
try {

	foreach ($rows as $row) {
		if ($row['key1'] == 'niceurls' || $row['key1'] == 'Niceurls' || $row['key1'] == 'n') {
			echo "n";
			if ($to_version == 4) {
				$model->set_niceurl4($row['key2'], $row['value']);
			} else {
				$model->set_niceurl($row['key2'], $row['value']);
			}
			if (!in_array($row['key2'], $instances_modified)) {
				$instances_modified[] = $row['key2'];
			}
			$nices++;
		} elseif ($row['key1'] == 'statics' || $row['key1'] == 'Statics' || $row['key1'] == 's') {
			echo "s";
			if ($to_version == 4) {
				$model->set_static4($row['key2'], $row['value']);
			} else {
				$model->set_static($row['key2'], $row['value']);
			}
			$statics++;
		} else {
			echo "v";
			//echo " inst_id=".$row['key1']." atri_id=".$row['key2']."\n";
			if ($to_version == 4) {
				$model->set_value4($row['key1'], $row['key2'], $row['value']);
			} else {
				$model->set_value($row['key1'], $row['key2'], $row['value']);
			}
			if (!in_array($row['key1'], $instances_modified)) {
				$instances_modified[] = $row['key1'];
			}
			$values++;
		}
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


	echo "\n";
// TBD inst_ids para eliminar la cache
	echo "$nices nice urls loaded!\n";
	echo "$statics static texts loaded!\n";
	echo "$values values loaded!\n";
	echo count($instances_modified) . " instances modified!\n";
} catch (\Exception $e) {
	$model->rollback();
	echo "Error found: " . $e->getMessage() . "\n";
	echo "Rolling back!!!\n";
	die;
}
$model->commit();
$end = microtime(true);
$seconds = round($end - $start, 2);
echo "\nFinished succesfully in $seconds seconds!\n";
