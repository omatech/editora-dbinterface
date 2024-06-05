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
	, 'outputformat:', 'tofilename:', 'what:', 'since:', 'parent_inst_id:', 'excludeclasses:', 'onlyclasses:'
	, 'help', 'includemetadata', 'debug', 'excludeimporteddata']);
//print_r($options_array);
if (isset($options_array['help'])) {
	echo 'Export strings in one language from editora database to excel file or output

From parameters:
--from= db4 | db5 
--dbhost= database host
--dbuser= database user
--dbpass= database password 
--dbname= database name 
--sourcelanguage= Source Language (ca|es|en...)
--since= date to extract from in mysql format
--parent_inst_id= Parent instance to get recursively all the content (pass only one inst_id or a list of comma separated inst_ids)
--excludeclasses= comma separated list of class_ids to avoid, for example --excludeclasses=3 or --excludeclasses=3,4
--onlyclasses= comma separated list of class_ids to include, for example --onlyclasses=3 or --onlyclasses=3,4

To parameters:
--to= file | output
--outputformat= (excel, json, array)
--tofilename= name of the file to export
--destinationlanguage= Destination Language (ca|es|en...)
--includemetadata if present add metadata in the output

Others:
--help this help!
--debug (if not present false)
--excludeimporteddata (default false, if present avoid instances with external_id not null)
--what= (all|missing|same) 
  - all: all the strings in a source language
	- missing: all the strings in source language that are empty in destination language
	- same: all the strings in source language that are the same in destinacion language (for review purposes)

example: 
	
1) Export missing texts in spanish that exists in english from a editora version 4 to an excel file
php export-translation.php --from=db4 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=demo --sourcelanguage=en --to=file --outputformat=excel --tofilename=missing_translation_from_en_to_es_demo.xlsx --destinationlanguage=es --what=missing

2) Export missing texts in spanish that exists in english from a editora version 5 to excel file
php export-translation.php --from=db5 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=demo5 --sourcelanguage=en --to=file --outputformat=excel --tofilename=missing_translation_from_en_to_es_demo5.xlsx --destinationlanguage=es --what=missing

3) Export missing texts in spanish that exists in english from a editora version 5 to standard output in array format
php export-translation.php --from=db5 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=demo5 --sourcelanguage=en --to=output --outputformat=array --destinationlanguage=es --what=missing 

4) Export missing texts in spanish that exists in english from a editora version 5 to standard output in json format
php export-translation.php --from=db5 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=demo5 --sourcelanguage=en --to=output --outputformat=json --destinationlanguage=es --what=missing 

5) Export all texts in english from a editora version 4 to an excel file
php export-translation.php --from=db4 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=demo --sourcelanguage=en --to=file --outputformat=excel --tofilename=all_en_demo.xlsx --destinationlanguage=es --what=all

6) Export all texts in english from a editora version 5 to an excel file
php export-translation.php --from=db5 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=demo5 --sourcelanguage=en --to=file --outputformat=excel --tofilename=all_en_demo5.xlsx --destinationlanguage=es --what=all

7) Export all texts in english from a editora version 5 to the output using json
php export-translation.php --from=db5 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=demo5 --sourcelanguage=en --to=output --outputformat=json --destinationlanguage=es --what=missing 

8) Export all texts in english from a editora version 4 to an excel file, avoiding classes 1 and 2
php export-translation.php --from=db4 --dbhost=localhost --dbuser=root --dbpass=xxx --dbname=demo5 --sourcelanguage=en --to=file --outputformat=excel --tofilename=../translatable_texts.xlsx --destinationlanguage=es --what=all --excludeclasses=1,2 

9) Export all texts in spanish from a editora version 4 to an excel file including only childs of inst_ids 308 and 307
php export-translation.php --from=db4 --dbhost=localhost --dbuser=root --dbpass= --dbname=demo --sourcelanguage=es --to=file --outputformat=excel --tofilename=only_childs.xlsx --destinationlanguage=po --what=all --parent_inst_id=308,307

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
	if (method_exists($dbal_config, 'setSQLLogger')) {
		$dbal_config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
	} else {
		$dbal_config->setMiddlewares([new \Doctrine\DBAL\Logging\Middleware(new \Psr\Log\NullLogger())]);
	}
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

	$conn = \Doctrine\DBAL\DriverManager::getConnection($connection_params, $dbal_config);
}


$params = [
	'source_language' => $options_array['sourcelanguage']
	, 'destination_language' => $options_array['destinationlanguage']
	, 'from_version' => $from_version
	, 'output_filename' => $options_array['tofilename']
	, 'what' => $options_array['what']
];

if (isset($options_array['since'])) {
	$params['since'] = $options_array['since'];
}

$params['excludeimporteddata'] = false;
if (isset($options_array['excludeimporteddata'])) {
	$params['excludeimporteddata'] = true;
}

$params['excludeclasses'] = null;
if (isset($options_array['excludeclasses'])) {
	$params['excludeclasses'] = $options_array['excludeclasses'];
}

$params['onlyclasses'] = null;
if (isset($options_array['onlyclasses'])) {
	$params['onlyclasses'] = $options_array['onlyclasses'];
}

if (isset($options_array['parent_inst_id'])) {
	$parent_inst_id = $options_array['parent_inst_id'];
} else {
	$parent_inst_id = null;
}

$result = array();

unset($options_array['dbpass']);
$result['metadata']['options'] = $options_array;
$result['metadata']['params'] = $params;
$result['metadata']['generated_at'] = time();
$result['metadata']['generated_at_human'] = date('Y-m-d H:i:s');

$model = new TranslatorModel($conn, $conn, $params, false);

if ($options_array['what'] == 'missing') {
	$rows = $model->get_missing_destination_texts();
} elseif ($options_array['what'] == 'all') {
	$rows = $model->get_all_source_texts();
} elseif ($options_array['what'] == 'same') {
	$rows = $model->get_same_as_destination_texts();
} else {
	die("Unknow what parameter, see --help for help. Aborting\n");
}


if ($parent_inst_id)
{
	$child_instances=[];
	//var_dump($model);
	$arr_parent_inst_id=explode(',',$parent_inst_id);
	foreach ($arr_parent_inst_id as $one_parent_inst_id)
	{
		$childs=$model->get_recursive_child_instances ($one_parent_inst_id);
		if ($childs)
		{
			foreach ($childs as $child)
			{
				if (!in_array($child, $child_instances))
				{
					$child_instances[]=$child;
				}
			}	
		}
	}
}


foreach ($rows['values'] as $val) {

	if ($parent_inst_id) 
	{
		if ($child_instances && in_array($val['inst_id'], $child_instances))
		{
			$result['data'][] = ['key1' => $val['inst_id'], 'key2' => $val['atri_id'], 'value' => $val['value']];
		}
	}
	else
	{// We're not filtering by parent
		$result['data'][] = ['key1' => $val['inst_id'], 'key2' => $val['atri_id'], 'value' => $val['value']];
	}

}

foreach ($rows['statics'] as $val) {
	if (!isset($options_array['onlyclasses'])) {// if we're exporting only classes don't export statics
		$result['data'][] = ['key1' => 'statics', 'key2' => $val['key'], 'value' => $val['value']];
	}
}

foreach ($rows['niceurls'] as $val) {
	if (!isset($options_array['onlyclasses'])) {// if we're exporting only classes don't export niceurls

		if ($parent_inst_id) 
		{
			if ($child_instances && in_array($val['inst_id'], $child_instances))
			{
				$result['data'][] = ['key1' => 'niceurls', 'key2' => $val['inst_id'], 'value' => $val['value']];
			}
		}
		else
		{// We're not filtering by parent
			$result['data'][] = ['key1' => 'niceurls', 'key2' => $val['inst_id'], 'value' => $val['value']];
		}
	}
}





ob_start('ob_gzhandler');
if ($options_array['outputformat'] == 'array') {
	if (isset($options_array['includemetadata'])) {
		print_r($result);
	} else {// only data
		print_r($result['data']);
	}
} elseif ($options_array['outputformat'] == 'json') {
	if (isset($options_array['includemetadata'])) {
		echo json_encode($result, JSON_PRETTY_PRINT);
	} else {// only data
		echo json_encode($result['data'], JSON_PRETTY_PRINT);
	}
} elseif ($options_array['outputformat'] == 'excel') {
	$objSpreadsheet = new Spreadsheet();
	// Set document properties
	$objSpreadsheet->getProperties()->setCreator("Omatech")
		->setLastModifiedBy("Omatech")
		->setTitle("Translator Export from editora from " . $options_array['sourcelanguage'] . " to " . $options_array['destinationlanguage'])
		->setSubject("Translator Export from editora")
		->setDescription("Translator Export from editora " . $options_array['dbname'] . " version $from_version at " . $result['metadata']['generated_at_human'] . " source language is " . $options_array['sourcelanguage'] . " detination language " . $options_array['destinationlanguage'])
		->setCategory("Translator");
	$objSpreadsheet->setActiveSheetIndex(0);
	$i = 1;
	$objSpreadsheet->getActiveSheet()->setTitle('Omatech Translator');
	$objSpreadsheet->getActiveSheet()->setCellValue("A$i", 'key1');
	$objSpreadsheet->getActiveSheet()->setCellValue("B$i", 'key2');
	$objSpreadsheet->getActiveSheet()->setCellValue("C$i", 'value');
	$i++;
	foreach ($result['data'] as $row) {
		$objSpreadsheet->getActiveSheet()->setCellValue("A$i", $row['key1']);
		$objSpreadsheet->getActiveSheet()->setCellValue("B$i", $row['key2']);
		$objSpreadsheet->getActiveSheet()->setCellValue("C$i", $row['value']);
		$i++;
	}
	$objWriter = IOFactory::createWriter($objSpreadsheet, 'Xlsx');
	$objWriter->save('php://output');
} else {
	die("Unknown output format, aborting!\n");
}

$output = ob_get_contents();
ob_end_clean();
if ($options_array['to'] == 'output') {
	echo $output;
} elseif ($options_array['to'] == 'file') {
	if (isset($options_array['tofilename'])) {
		file_put_contents($options_array['tofilename'], $output);
	} else {
		die("You must especify a valid filename with tofilename parameter when outputing to file. Aborting\n");
	}
} else {
	die('Unknown to parameter, aborting!\n');
}


