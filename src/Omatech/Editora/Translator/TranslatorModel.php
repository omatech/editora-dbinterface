<?php

//à

namespace Omatech\Editora\Translator;

class TranslatorModel extends AppModel {

	function __construct($conn_from, $conn_to, $params=array(), $debug = false) {// requires doctrine dbal connection or array with data			
		foreach ($params as $key => $val) {
			$this->$key = $val;
		}
		if (!isset($this->since)) $this->since="1970-01-01";
		return parent::__construct($conn_from, $conn_to);
	}

	function translate_attribute_id($atri_id_from, $source_language, $destination_language, $connection = 'conn_to') {
		$sql = "select * 
		from omp_attributes 
		where id=$atri_id_from 
		and language=" . parent::escape($source_language, $connection) . "
		";
		$row = parent::get_one($sql, $connection);
		if (!$row)
			die("Critical error atri_id=$atri_id_from not found in language $source_language!\n");

		$sql="select id from omp_attributes 
		where language=" . parent::escape($destination_language, $connection) . "
		and tag=". parent::escape($row['tag'], $connection) . "
		";
		$row = parent::get_one($sql, $connection);
		if (!$row)
			die("Critical error source atri_id=$atri_id_from not found in language $destination_language!\n");
		
		/*
		$offsetlang=$this->offsetlang;
		$destination_lang_id = $this->get_language_id($destination_language);
		$destination_lang_id = $destination_lang_id * $offsetlang;
		$multilang_atri_id = $atri_id_from % $offsetlang;
		$translated_atri_id = $destination_lang_id + $multilang_atri_id;
		*/
		$translated_atri_id=$row['id'];
		
		return $translated_atri_id;
	}

	function get_language_id($lang, $connection = 'conn_to') {
		
		$lengthoffsetlang = strlen($this->offsetlang)-1;
		$sql = "select substring(min(id),1,length(min(id))-$lengthoffsetlang) id, language
		from omp_attributes 
		where language!='ALL'
		and language=" . parent::escape($lang, $connection) . "
		group by language order by id";
		$row = parent::get_one($sql, $connection);

		return $row['id'];
	}

	function set_value ($inst_id, $atri_id_from, $value, $connection = 'conn_to') {
		$atri_id_to = $this->translate_attribute_id($atri_id_from, $this->source_language, $this->destination_language);
		$sql = "update omp_values
		set deleted_at=now()
		where deleted_at is null
		and inst_id=$inst_id
		and atri_id=$atri_id_to
		";
		parent::executeQuery($sql, $connection);

		$sql = "insert into omp_values (inst_id, atri_id, value, created_at, translated_at)
		values ($inst_id, $atri_id_to, " . parent::escape($value, $connection) . ", now(), now());
		";
		$ret = parent::insert_one($sql, $connection);
		return $ret;
	}

	function set_value4 ($inst_id, $atri_id_from, $value, $connection = 'conn_to') {
		if (!isset($inst_id) || !isset($atri_id_from) || !is_numeric($inst_id) || !is_numeric($atri_id_from))
		{
			die ("\nLos valores de inst_id ($inst_id) y atri_id_from ($atri_id_from) no existen o no son numéricos\n");			
		}
		$atri_id_to = $this->translate_attribute_id($atri_id_from, $this->source_language, $this->destination_language);
		$sql = "delete from omp_values
		where inst_id=$inst_id
		and atri_id=$atri_id_to
		";
		parent::executeQuery($sql, $connection);

		$sql = "insert into omp_values (inst_id, atri_id, text_val)
		values ($inst_id, $atri_id_to, " . parent::escape($value, $connection) . ");
		";
		$ret = parent::insert_one($sql, $connection);
		return $ret;
	}

	function set_static($key, $value, $connection = 'conn_to') {
		$sql = "update omp_static_text
		set deleted_at=now()
		where deleted_at is null
		and `key`=" . parent::escape($key, $connection) . "
		and language=" . parent::escape($this->destination_language, $connection) . "
		";
		parent::executeQuery($sql, $connection);

		$sql = "insert into omp_static_text (`key`, language, `value`, created_at, translated_at)
		values (" . parent::escape($key, $connection) . ", " . parent::escape($this->destination_language, $connection) . ", " . parent::escape($value, $connection) . ", now(), now());
		";
		$ret = parent::insert_one($sql, $connection);
		return $ret;
	}


	function set_static4($key, $value, $connection = 'conn_to') {
		$sql = "delete from omp_static_text
		where `text_key`=" . parent::escape($key, $connection) . "
		and language=" . parent::escape($this->destination_language, $connection) . "
		";
		parent::executeQuery($sql, $connection);

		$sql = "insert into omp_static_text (`text_key`, language, `text_value`)
		values (" . parent::escape($key, $connection) . ", " . parent::escape($this->destination_language, $connection) . ", " . parent::escape($value, $connection) . ");
		";
		$ret = parent::insert_one($sql, $connection);
		return $ret;
	}

	
	function update_instance4 ($inst_id, $connection = 'conn_to')
	{
		$sql = "update omp_instances
		set update_date=now()
		where id=$inst_id
		";	
		parent::executeQuery($sql, $connection);
	}
	
	function update_instance5 ($inst_id, $connection = 'conn_to')
	{
		$sql = "update omp_instances
		set updated_at=now()
		where id=$inst_id
		";	
		parent::executeQuery($sql, $connection);
	}	

	function set_niceurl($inst_id, $value, $connection = 'conn_to') {
		$sql = "update omp_niceurl
		set deleted_at=now()
		where deleted_at is null
		and inst_id=$inst_id
		and language=" . parent::escape($this->destination_language, $connection) . "
		";
		parent::executeQuery($sql, $connection);

		$sql = "insert into omp_niceurl (inst_id, language, niceurl, created_at, translated_at)
		values ($inst_id, " . parent::escape($this->destination_language, $connection) . ", " . parent::escape($value, $connection) . ", now(), now());
		";
		$ret = parent::insert_one($sql, $connection);
		return $ret;
	}

	function set_niceurl4($inst_id, $value, $connection = 'conn_to') 
	{

		$value= \Omatech\Editora\Utils\Strings::urlnicify($value);
		
		if ($value=='') $value=$inst_id;
		
		$sql="select id 
		from omp_attributes 
		where tag='niceurl' 
		and language=" . parent::escape($this->destination_language, $connection) . "
		";
		$atri_id=parent::fetchColumn($sql, $connection);
		
		$sql="delete
		from omp_values
		where inst_id=$inst_id
		and atri_id=$atri_id";
		//echo "$sql\n";
		parent::executeQuery($sql, $connection);
		
		$sql="insert into omp_values (inst_id, atri_id, text_val) 
		values ($inst_id, $atri_id, " . parent::escape($value, $connection) . ")";
		parent::executeQuery($sql, $connection);
		//echo "$sql\n";

		$sql="delete from omp_niceurl
		where inst_id=$inst_id
		and language=" . parent::escape($this->destination_language, $connection) . "
		";
		//echo "$sql\n";
		parent::executeQuery($sql, $connection);

		$sql="select count(*) num from omp_niceurl where language=" . parent::escape($this->destination_language, $connection) . " and niceurl=" . parent::escape($value, $connection) . "";
		$num_nices=parent::fetchColumn($sql, $connection);
		
		if ($num_nices==0)
		{
			$sql = "insert into omp_niceurl (inst_id, language, niceurl)
			values ($inst_id, " . parent::escape($this->destination_language, $connection) . ", " . parent::escape($value, $connection) . ");
			";
		}
		else
		{
			$sql = "insert into omp_niceurl (inst_id, language, niceurl)
			values ($inst_id, " . parent::escape($this->destination_language, $connection) . ", " . parent::escape($value.uniqid(), $connection) . ");
			";			
		}
		//echo "$sql\n";
		$ret = parent::insert_one($sql, $connection);
		return $ret;
	}

	function set_update_date_in_instances($instances_array, $connection = 'conn_to') {
		$sql = "update omp_instances
		set updated_at=now()
		where inst_id in (" . implode(',', $instances_array) . ")
		";
		parent::executeQuery($sql, $connection);
	}

	function get_all_source_texts($connection = 'conn_from') {
		
		$imported_sql_add="";
		$exclude_classes_sql_add="";
		if ($this->excludeimporteddata) $imported_sql_add=" and i.external_id is not null ";
		if ($this->excludeclasses) $exclude_classes_sql_add=" and i.class_id not in (".$this->excludeclasses.") ";
		if ($this->onlyclasses) $only_classes_sql_add=" and i.class_id in (".$this->onlyclasses.") ";
		
		if ($this->from_version == 4) {
			$sql_values = "select v.inst_id, v.atri_id, v.text_val value 
			from omp_attributes a
			, omp_values v
			, omp_instances i
			where v.text_val is not null
			and v.atri_id=a.id
			and v.atri_id!=1
			and v.inst_id=i.id
			and i.status='O'
			$imported_sql_add
			$exclude_classes_sql_add
			$only_classes_sql_add
			and a.language=" . parent::escape($this->source_language, $connection) . "
			and i.update_date >= ".parent::escape($this->since)."
			order by v.inst_id, v.atri_id
			";

			$sql_exists = "show tables like 'omp_static_text'";
			$row = parent::get_one($sql_exists, $connection);
			if ($row) {
				$sql_statics = "select st1.text_key `key`, st1.text_value value
				from omp_static_text st1
				where st1.language = " . parent::escape($this->source_language, $connection) . "
				";
			} else {// posem una query que no retornara resultats perque no tenim la taula
				$sql_statics = "select -1 `key`, -1 value
				from omp_instances where id=-1000000
				";
			}

			$sql_niceurls = "select inst_id, niceurl value
				from  omp_niceurl s 
				where s.language=" . parent::escape($this->source_language, $connection) . "
			";
		} elseif ($this->from_version == 5) {
			$sql_values = "select v.inst_id, v.atri_id, v.value
			from omp_attributes a
			, omp_values v
			, omp_instances i
			where v.value is not null
			and v.deleted_at is null
			and v.atri_id=a.id
			and v.inst_id=i.id
			and i.status='O'
			$imported_sql_add
			$exclude_classes_sql_add
			$only_classes_sql_add
			and i.deleted_at is not null
			and a.language=" . parent::escape($this->source_language, $connection) . "
			and i.updated >= ".parent::escape($this->since)."
			order by v.inst_id, v.atri_id
			";

			$sql_statics = "select st1.`key`, st1.`value` 
			from omp_static_text st1
			where st1.language = " . parent::escape($this->source_language, $connection) . "
			and st1.deleted_at is null
			";

			$sql_niceurls = "select inst_id, niceurl value
			from  omp_niceurl s 
			where s.language=" . parent::escape($this->source_language, $connection) . "
			and s.deleted_at is null
			";
		} else {
			die("Unknow version, aborting!\n");
		}

		$values_array = parent::fetchAll($sql_values, $connection);
		$static_texts_array = parent::fetchAll($sql_statics, $connection);
		$niceurls_array = parent::fetchAll($sql_niceurls, $connection);

		$result = array();
		$result['values'] = $values_array;
		$result['statics'] = $static_texts_array;
		$result['niceurls'] = $niceurls_array;
		return $result;
	}

	function get_missing_destination_texts($connection = 'conn_from') 
	{
		$imported_sql_add="";
		$exclude_classes_sql_add="";
		$only_classes_sql_add="";
		if ($this->excludeimporteddata) $imported_sql_add=" and i.external_id is not null ";
		if ($this->excludeclasses) $exclude_classes_sql_add=" and i.class_id not in (".$this->excludeclasses.") ";
		if ($this->onlyclasses) $only_classes_sql_add=" and i.class_id in (".$this->onlyclasses.") ";

		if ($this->from_version == 4) {
			$sql_values = "select v.inst_id, v.atri_id, v.text_val value 
			from omp_attributes a
			, omp_values v
			, omp_attributes a2
			, omp_instances i
			where v.text_val is not null
			and v.atri_id=a.id
			and v.atri_id!=1
			and a.language=" . parent::escape($this->source_language, $connection) . "
			and a.tag=a2.tag
			and a2.language=" . parent::escape($this->destination_language, $connection) . "
			and v.inst_id=i.id
			and i.status='O'
			$imported_sql_add
			$exclude_classes_sql_add
			$only_classes_sql_add
			and i.update_date >= ".parent::escape($this->since)."
			and not exists 
			  (select 1 
				from omp_values v2 
				where v2.atri_id=a2.id 
				and v2.inst_id=v.inst_id 
				and v2.text_val!=''
				)
			
			";

			$sql_exists = "show tables like 'omp_static_text'";
			$row = parent::get_one($sql_exists, $connection);
			if ($row) {
				$sql_statics = "select st1.text_key `key`, st1.text_value value
				from omp_static_text st1
				where st1.language = " . parent::escape($this->source_language, $connection) . "
				and st1.text_key not in 
					(select st2.text_key 
					from omp_static_text st2 
					where st2.language=" . parent::escape($this->destination_language, $connection) . " 
					and st2.text_value!=''
					)
				";
			} else {// posem una query que no retornara resultats perque no tenim la taula
				$sql_statics = "select -1 `key`, -1 value
				from omp_instances where id=-1000000
				";
			}
			$sql_niceurls = "select inst_id, niceurl value
				from  omp_niceurl s 
				where s.language=" . parent::escape($this->source_language, $connection) . "
				and not exists 
					(select 1 
					from omp_niceurl d 
					where d.inst_id=s.inst_id 
					and d.language=" . parent::escape($this->destination_language, $connection) . " 
					)		
			";
		} elseif ($this->from_version == 5) {
			$sql_values = "select v.inst_id, v.atri_id, v.value
			from omp_attributes a
			, omp_values v
			, omp_attributes a2
			, omp_instances i
			where v.value is not null
			and v.atri_id=a.id
			and a.language=" . parent::escape($this->source_language, $connection) . "
			and a.name=a2.name
			and a2.language=" . parent::escape($this->destination_language, $connection) . " 
			and v.deleted_at is null
			and v.inst_id=i.id
			and i.status='O'
			$imported_sql_add
			$exclude_classes_sql_add
			$only_classes_sql_add
			and i.deleted_at is null
			and i.updated >= ".parent::escape($this->since)."
			and not exists 
			  (select 1 
				from omp_values v2 
				where v2.atri_id=a2.id 
				and v2.inst_id=v.inst_id 
				and v2.value!=''
				and v2.deleted_at is null
				)
			";

			$sql_statics = "select st1.`key`, st1.`value` 
			from omp_static_text st1
			where st1.language = " . parent::escape($this->source_language, $connection) . "
			and st1.deleted_at is null
			and st1.`key` not in 
			  (select st2.`key` 
				from omp_static_text st2 
			  where st2.language=" . parent::escape($this->destination_language, $connection) . "
				and st2.`value`!=''
				and st2.deleted_at is null
				)
			";

			$sql_niceurls = "select inst_id, niceurl value
				from  omp_niceurl s 
				where s.language=" . parent::escape($this->source_language, $connection) . "
				and s.deleted_at is null
				and not exists 
					(select 1 
					from omp_niceurl d 
					where d.inst_id=s.inst_id 
					and d.language=" . parent::escape($this->destination_language, $connection) . " 
					and d.deleted_at is null
					)
				";
		} else {
			die("Unknow version, aborting!\n");
		}

		$values_array = parent::fetchAll($sql_values, $connection);
		$static_texts_array = parent::fetchAll($sql_statics, $connection);
		$niceurls_array = parent::fetchAll($sql_niceurls, $connection);

		$result = array();
		$result['values'] = $values_array;
		$result['statics'] = $static_texts_array;
		$result['niceurls'] = $niceurls_array;
		return $result;
	}

	
	function get_same_as_destination_texts($connection = 'conn_from') 
	{
		$imported_sql_add="";
		$exclude_classes_sql_add="";
		if ($this->excludeimporteddata) $imported_sql_add=" and i.external_id is not null ";
		if ($this->excludeclasses) $exclude_classes_sql_add=" and i.class_id not in (".$this->excludeclasses.") ";
		if ($this->onlyclasses) $only_classes_sql_add=" and i.class_id in (".$this->onlyclasses.") ";

		if ($this->from_version == 4) {
			$sql_values = "select v.inst_id, v.atri_id, v.text_val value 
			from omp_attributes a
			, omp_values v
			, omp_attributes a2
			, omp_instances i
			where v.text_val is not null
			and v.atri_id=a.id
			and v.atri_id!=1
			and a.language=" . parent::escape($this->source_language, $connection) . "
			and a.tag=a2.tag
			and a2.language=" . parent::escape($this->destination_language, $connection) . "
			and v.inst_id=i.id
			and i.status='O'
			$imported_sql_add
			$exclude_classes_sql_add
			$only_classes_sql_add
			and i.update_date >= ".parent::escape($this->since)."
			and v.text_val=v2.text_val
			and v2.text_val!=''
			";

			$sql_exists = "show tables like 'omp_static_text'";
			$row = parent::get_one($sql_exists, $connection);
			if ($row) {
				$sql_statics = "select st1.text_key `key`, st1.text_value value
				from omp_static_text st1
				, omp_static_text st2
				where st1.language = " . parent::escape($this->source_language, $connection) . "
				and st1.text_key=st2.text_key 
				and st2.language=" . parent::escape($this->destination_language, $connection) . " 
				and st2.text_value!=''
				and st1.text_value=st2.text_value
				";
			} else {// posem una query que no retornara resultats perque no tenim la taula
				$sql_statics = "select -1 `key`, -1 value
				from omp_instances where id=-1000000
				";
			}
			$sql_niceurls = "select inst_id, niceurl value
				from  omp_niceurl s 
				, omp_niceurl d
				where s.language=" . parent::escape($this->source_language, $connection) . "
				and d.inst_id=s.inst_id 
				and d.language=" . parent::escape($this->destination_language, $connection) . " 
				and d.niceurl=s.niceurl
			";
		} elseif ($this->from_version == 5) {
			die("Incompatible version, aborting!\n");
		} else {
			die("Unknow version, aborting!\n");
		}

		$values_array = parent::fetchAll($sql_values, $connection);
		$static_texts_array = parent::fetchAll($sql_statics, $connection);
		$niceurls_array = parent::fetchAll($sql_niceurls, $connection);

		$result = array();
		$result['values'] = $values_array;
		$result['statics'] = $static_texts_array;
		$result['niceurls'] = $niceurls_array;
		return $result;
	}
	
	
	
}
