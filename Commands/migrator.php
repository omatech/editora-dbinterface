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

use \Doctrine\DBAL\Configuration;

ini_set("memory_limit", "5000M");
set_time_limit(0);

$default_language='es';
$input_processed=false;
$output_processed=false;
$input_action='';
$output_action='';
$data_transfered=false;

$options_array = getopt(null, ['from::', 'to::'
	, 'dbfromuser:', 'dbfrompass:', 'dbfromhost:', 'dbfromname:', 'fromfile:'
	, 'dbtouser:', 'dbtopass:', 'dbtohost:', 'dbtoname:'
	, 'help', 'transferdata']);
//print_r($options_array);
if (isset($options_array['help'])) {
	echo 'Use migrator.php to extract editora4 information and get it into editora5 format, transfer directly to a db5 database or generate 3 different file formats (full, minimal, generator)
From parameters:
--from= db4 | db5 | editora5generatorfile
--dbfromuser= database user from
--dbfrompass= database password from
--dbfromhost= database host from
--dbfromname= database name from
--fromfile= filename or url

To parameters:
--to= db5 | editora5file | editora5minimalfile | editora5generatorfile
--dbtouser= database user to
--dbtopass= database password to
--dbtohost= database host to
--dbtoname= database name to

Others:
--help this help!
--transferdata if present try to transfer data between databases, only works from=db4 to=db5 and if you are in the same host and connected as root

example: 
	
1) extract from an editora4 database to an editora5minimalfile
php migrator.php --from=db4 --to=editora5minimalfile --dbfromuser=root --dbfrompass=xxx --dbfromhost=localhost --dbfromname=panreac

2) extract from an editora4 database to an editora5 database
php migrator.php --from=db4 --to=db5 --dbfromuser=root --dbfrompass=xxx --dbfromhost=localhost --dbfromname=panreac --dbtouser=root --dbtopass=xxx --dbtohost=localhost --dbtoname=panreac5

3) extract from an editora4 database to an editora5 database transfering data
php migrator.php --transferdata --from=db4 --to=db5 --dbfromuser=root --dbfrompass=xxx --dbfromhost=localhost --dbfromname=panreac --dbtouser=root --dbtopass=xxx --dbtohost=localhost --dbtoname=panreac5

4) extract from an editora5 database to an editora5 minimal file
php migrator.php --from=db5 --to=editora5minimalfile --dbfromuser=root --dbfrompass=xxx --dbfromhost=localhost --dbfromname=panreac5

5) extract from an editora5 database to an editora5 generator file
php migrator.php --from=db5 --to=editora5generatorfile --dbfromuser=root --dbfrompass=xxx --dbfromhost=localhost --dbfromname=panreac5

6) extract from an editora5 database to an editora5 generator file (sending to a json file)
php migrator.php --from=db5 --to=editora5generatorfile --dbfromuser=root --dbfrompass=xxx --dbfromhost=localhost --dbfromname=panreac5 > panreac5generator.json
 
7) import from an editora5 generator file to an editora5 generatorfile (identity)
php migrator.php --from=editora5generatorfile --fromfile=panreac5generator.json --to=editora5generatorfile 

8) import from an editora5 generator file to an editora5 database
php migrator.php --from=editora5generatorfile --fromfile=panreac5generator.json --to=db5 --dbtouser=root --dbtopass=xxx --dbtohost=localhost --dbtoname=panreac5
';
	die;
}

if (!isset($options_array['from']) || !isset($options_array['to'])) {
	echo "Missing from or to parameters, use --help for help!\n";
	die;
}

$to_version = 4;
if ($options_array['to'] == 'editora5file' || $options_array['to'] == 'editora5minimalfile' || $options_array['to'] == 'db5'|| $options_array['to'] == 'editora5generatorfile') {
	$to_version = 5;
}

$from_version = 4;
if ($options_array['from'] == 'db5' || $options_array['from'] == 'editora5file' || $options_array['from'] == 'editora5minimalfile'|| $options_array['to'] == 'editora5generatorfile') {
	$from_version = 5;
}

$minimal = false;
if (stripos($options_array['to'], 'minimalfile') || $options_array['to']=='editora5generatorfile') {
	$minimal = true;
}

$dbal_config = new \Doctrine\DBAL\Configuration();

$conn_to = null;
if ($options_array['from'] == 'db4' || $options_array['from'] == 'db5') {
	$connection_params_from = array(
		'dbname' => $options_array['dbfromname'],
		'user' => $options_array['dbfromuser'],
		'password' => $options_array['dbfrompass'],
		'host' => $options_array['dbfromhost'],
		'driver' => 'pdo_mysql',
		'charset' => 'utf8'
	);

	$conn_to = \Doctrine\DBAL\DriverManager::getConnection($connection_params_from, $dbal_config);
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
	'default_language' => $default_language
	, 'to_version' => $to_version
	, 'minimal' => $minimal
	, 'from_version' => $from_version
];

if (isset($options_array['dbfromname']))
{
	$params['from_dbname']=$options_array['dbfromname'];
}

if (isset($options_array['dbtoname']))
{
	$params['to_dbname']=$options_array['dbtoname'];
}


$result = array();

unset($options_array['dbfrompass']);
unset($options_array['dbtopass']);
$result['metadata']['options'] = $options_array;
$result['metadata']['params'] = $params;
$result['metadata']['generated_at'] = time();
$result['metadata']['generated_at_human'] = date('Y-m-d H:i:s');

$model = new \Omatech\Editora\Migrator\Migrator($conn_to, $conn_to, $params, false);

if ($options_array['from'] == 'db4' || $options_array['from'] == 'db5') {
	
	$result['roles'] = $model->get_roles();
	$result['languages'] = $model->get_languages();
	$result['tabs'] = $model->get_tabs();
	$result['users'] = $model->get_users();
	$result['class_groups'] = $model->get_class_groups();
	$result['relations'] = $model->get_relations();
	$result['attributes'] = $model->get_attributes();
	$result['classes'] = $model->get_classes();

	$input_processed=true;
	$input_action='db';
}

if ($options_array['from'] == 'editora5generatorfile')
{
	$input_file_contents=file_get_contents($options_array['fromfile']);
	$input_array=json_decode($input_file_contents, true);
	
	$result['attributes']=$model->transform_attributes_fromgenerator5_to5($input_array);	
	$result['relations']=$model->transform_relations_fromgenerator5_to5($input_array);
	$result['classes']=$model->transform_classes_fromgenerator5_to5($input_array);	
	$result['class_groups']=$model->transform_class_groups_fromgenerator5_to5($input_array);	
	$result['languages']=$model->transform_languages_fromgenerator5_to5($input_array);	
	$result['tabs']=$model->transform_tabs_fromgenerator5_to5($input_array);	
	$result['users']=$model->transform_users_fromgenerator5_to5($input_array);	
	$result['roles']=$model->transform_roles_fromgenerator5_to5($input_array);	
			
	$input_processed=true;
	$input_action='generator';

}

if (($options_array['from'] == 'db4' || $options_array['from'] == 'editora5generatorfile') && $options_array['to'] == 'db5') {

	$model->start_transaction('conn_to');
	$ret = $model->set_roles($result['roles']);
	echo "$ret roles inserted\n";
	$ret = $model->set_languages($result['languages']);
	echo "$ret languages processed\n";
	$ret = $model->set_tabs($result['tabs']);
	echo "$ret tabs inserted\n";
	$ret = $model->set_users($result['users']);
	echo "$ret users inserted\n";
	$ret = $model->set_class_groups($result['class_groups']);
	echo "$ret class_groups inserted\n";
	$ret = $model->set_relations($result['relations']);
	echo "$ret relations inserted\n";
	$ret = $model->set_attributes($result['attributes']);
	echo "$ret attributes inserted\n";
	$ret = $model->set_classes($result['classes']);
	echo "$ret classes inserted\n";
	$model->commit('conn_to');

	$output_processed=true;
	$output_action='db';
}

if ($options_array['from'] == 'db4' && $options_array['to'] == 'db5'
&& $options_array['dbfromuser'] == 'root' 
&& $options_array['dbfromhost']==$options_array['dbtohost']
&& isset($options_array['transferdata'])) 
{
	$model->transfer_data_from4_to5();

	$data_transfered=true;
}

if ($options_array['to'] == 'editora5file' || $options_array['to'] == 'editora5minimalfile') {
	echo json_encode($result, JSON_PRETTY_PRINT);

	$output_processed=true;
	$output_action='file';	
}

if ($options_array['to']=='editora5generatorfile' && $from_version==5)
{
	$generator_array=array();
	$generator_array['metadata']=$result['metadata'];

	$generator_array['attributes']=$model->transform_attributes_from5_togenerator5($result, $default_language);
	$generator_array['relations']=$model->transform_relations_from5_togenerator5($result);
	$generator_array['classes']=$model->transform_classes_from5_togenerator5($result);
	$generator_array['class_groups']=$model->transform_class_groups_from5_togenerator5($result);
	$generator_array['users']=$model->transform_users_from5_togenerator5($result);
	$generator_array['languages']=$model->transform_languages_from5_togenerator5($result);
	
	echo json_encode($generator_array, JSON_PRETTY_PRINT);	
	
	$output_processed=true;
	$output_action='file';	

}

if ($options_array['from'] == 'db4' && $options_array['to'] == 'db4') 
{// direct transfer
		echo "Run that command on your own risk!\n";
		echo "mysqldump --default-character-set=utf8 -u ".$options_array['dbfromuser']." -p".$options_array['dbfrompass']." ".$options_array['dbfromhost']." ".$options_array['dbfromname']." \
			omp_attributes omp_class_attributes omp_class_groups omp_classes omp_lookups omp_lookups_values omp_relations omp_roles omp_roles_classes omp_tabs omp_users \
			mysql --default-character-set=utf8 -u ".$options_array['dbtouser']." -p".$options_array['dbtopass']." ".$options_array['dbtohost']." ".$options_array['dbtoname']."\n\n";
		$output_processed=true;
		$output_action='command line';
	
}

if (!$input_processed || !$output_processed)
{
	echo "Something weird happen, input or output are not been processed, please review the parameters\n
	input_action=$input_action
	output_action=$output_action
	".print_r($options_array);
	
}







