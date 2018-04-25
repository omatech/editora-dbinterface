# Editora-Extractor

Extract info from omatech Editora using closure Eloquent like functions

## First make a new instance

// Create a new database connection
$connectionParams = array(
    'dbname' => 'xxx',
    'user' => 'xxx',
    'password' => 'xxx',
    'host' => 'xxx',
    'driver' => 'pdo_mysql',
		'charset' => 'utf8mb4'
);
$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, new \Doctrine\DBAL\Configuration());

// Set-up the global params of the extraction (language,...) (See the "Global Params" section for more info)
$params = [
	'lang' => 'es'
	, 'debug' => false
	, 'metadata' => true
];
	
// Instantiate the extractor using the connection and params			
$e=new Extractor($conn, $params);

## Extract information using the extractor

### findInstanceById($inst_id, $params, callable $callback = null);
- * inst_id (id of the instance to be extracted)
- params (particular params for this extraction, see "Extraction Params" and "Instance Params" for more info)
- callback (closure function)

### findInstancesInClass($class, $num=null, $params=null, callable $callback = null)
- * class (id or tag of the class)
- num (number of instances to extract, if not set get all instances of the class)
- params (particular params for this extraction, see "Extraction Params" and "Instance Params" for more info)
- callback (closure function)

#### Extraction params
- order = order class instances by order criteria, update_date|publishing_begins|inst_id|key_fields default publishing_begins
- order_direction = direction of the order by clause, desc|asc defaults to asc


### findInstancesInList($inst_ids, $params = null, callable $callback = null)
- *inst_ids (comma separated ids of instances to extract)
- params (particular params for this extraction, see "Extraction Params" and "Instance Params" for more info)
- callback (closure function)

Note: The order of the extraction is forced by the order of the IDs in the list


### findInstancesBySearch($query, $num=null, $class=null, $params = null, callable $callback = null)
- * query (the search term)
- num (number of instances to extract, if not set get all instances of the class)
- class (filter one particular class by tag or id)
- params (particular params for this extraction, see "Extraction Params" and "Instance Params" for more info)
- callback (closure function)

Note: The order of the extraction is forced by the relevance of the search term (query)


### findRelatedInstances($inst_id, $relation, $num=null, $params = null, callable $callback = null)
- * inst_id (parent or child instance to start search)
- * relation (tag or id of the relation)
- num (number of instances to extract, if not set get all instances of the class)
- params (particular params for this extraction, see "Extraction Params" and "Instance Params" for more info)
- callback (closure function)

#### Extraction params
- direction (child|parent) Allows to force the direction of the relation for cases where the relation is parent and child of a class, if not set tries to find the direction automatically
- alias (string) (default tag of the relation) alias of the relation to extract


### findChildrenInstances($inst_id, $relation, $num=null, $params = null, callable $callback = null)
(same as findRelatedInstances but forcing child direction)

### findParentInstances($inst_id, $relation, $num=null, $params = null, callable $callback = null)
(same as findRelatedInstances but forcing parent direction)

## Instance Params

The instance params affect the behaviour of the extraction of the instance itself

- filter (string) (default all) 
  - all - extracts all the attributes of the instance (even if it's value is null)
  - detail - extracts only attributes with the class_attribute.detail='Y'
  - resume - extracts only attributes with the class_attribute.detail='N'
	- only-X - are values only of the attribute_type=X (for example S for string type only)
	- except-Y  are values excluding attribute_type=Y (for example K to avoid K attributes that are usually long)
  - fields:fieldname1|fieldname2 

## Additional calls

### findClass($class)
- * class (filter one particular class by tag or id)

## findRelation($relation)
- * relation (tag or id of the relation)

## Global Params

The format of the params is a key value par in an array, for example:

$params = [
	'lang' => 'es'
	, 'debug' => false
	, 'metadata' => true
  . 'show_inmediate_debug' => true
];

The global params can be:

- lang (2 characters ISO language code or ALL) (default ALL) ('ALL' for only non-language dependent attributes, 'es' for spanish and non-language dependent attributes, 'en' for english, etc.
- debug (boolean) (default false) Enable debugging, you can check the debug messages afterwards using $e->debug_messages
- show_inmediate_debug (boolean) (default false) If debugging is enabled echo directly the debug messages in addition to store them in debug_messages
- metadata (boolean) (default false) Enable metadata output, info like status, publishing_begins, publishing_ends etc
- default_limit (int) (default 10000) - Set the default limit when no limit is set in the call
- cache_expiration (int) (default 3600 (1 hour)) seconds to expire cached instances
- avoid_cache (boolean) (default false) Prevents the use of the cache system
- preview (boolean) (default false) Sets the extractor in preview mode
- preview_date (string mysql date) (default "NOW()") Sets the preview_date of the extractor, only make sense if preview=true, for example preview an extraction in '2020-01-01'
- extract_values (boolean) (default true) Extracts values of the instance (even null)
- sql_select_instances (mysql select string) (default: select i.*, c.name class_name, c.tag class_tag, c.id class_id, i.key_fields nom_intern, i.update_date, ifnull(unix_timestamp(i.update_date),0) update_timestamp)
- timings (boolean) default false show start and end and total milliseconds for each extraction, only make sense if metadata is true

## Extraction params


## Tests

Run tests using:

phpunit tests/

## Authors

Agusti Pons
Christian Bohollo
Javier Mogollon


## License

This project is licensed under the MIT License 

