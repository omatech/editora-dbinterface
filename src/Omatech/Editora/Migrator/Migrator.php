<?php

//à

namespace Omatech\Editora\Migrator;
use \Omatech\Editora\DBInterfaceBase;

class Migrator extends DBInterfaceBase {
	
	public $to_languages;
	
	function transfer_data_from4_to5()
	{
		$sql="truncate table ".$this->to_dbname.".omp_values";
		parent::executeQuery($sql);
		echo "omp_values in ".$this->to_dbname." truncated!\n";
		
		$sql="insert into ".$this->to_dbname.".omp_values (id, inst_id, atri_id, value, created_at)
		(select id, inst_id, atri_id, ifnull(text_val, ifnull(num_val, date_val)), now() 
		from ".$this->from_dbname.".omp_values where atri_id!=1)\n";
		//echo $sql;
		$updated=parent::executeUpdate($sql);
		echo "$updated omp_values transfered!\n";
		

		$sql="truncate table ".$this->to_dbname.".omp_instances";
		parent::executeQuery($sql);
		echo "omp_instances in ".$this->to_dbname." truncated!\n";
		
		$sql="insert into ".$this->to_dbname.".omp_instances (id, class_id, key_fields, status, publishing_begins, publishing_ends, created_at, updated_at, deleted_at)
		(select id, class_id, key_fields, status, publishing_begins, publishing_ends, creation_date, update_date, null 
		from ".$this->from_dbname.".omp_instances)\n";
		//echo $sql."\n";
		$updated=parent::executeUpdate($sql);
		echo "$updated omp_instances transfered!\n";
		

		$sql="truncate table ".$this->to_dbname.".omp_niceurl";
		parent::executeQuery($sql);
		echo "omp_niceurl in ".$this->to_dbname." truncated!\n";
		
		$sql="insert into ".$this->to_dbname.".omp_niceurl (id, inst_id, language, niceurl, created_at)
		(select id, inst_id, language, niceurl, now() 
		from ".$this->from_dbname.".omp_niceurl)\n";
		//echo $sql."\n";
		$updated=parent::executeUpdate($sql);
		echo "$updated omp_niceurl transfered!\n";
		
		
		$sql="truncate table ".$this->to_dbname.".omp_relation_instances";
		parent::executeQuery($sql);
		echo "omp_relation_instances in ".$this->to_dbname." truncated!\n";
		
		$sql="insert into ".$this->to_dbname.".omp_relation_instances (id, rel_id, parent_inst_id, child_inst_id, weight, created_at, clone_session, cloned_instance)
		(select id, rel_id, parent_inst_id, child_inst_id, weight, relation_date, clone_session, cloned_instance 
		from ".$this->from_dbname.".omp_relation_instances)\n";
		//echo $sql."\n";
		$updated=parent::executeUpdate($sql);
		echo "$updated omp_relation_instances transfered!\n";


		$sql="truncate table ".$this->to_dbname.".omp_user_instances";
		parent::executeQuery($sql);
		echo "omp_user_instances in ".$this->to_dbname." truncated!\n";
		
		$sql="insert into ".$this->to_dbname.".omp_user_instances (id, user_id, inst_id, access_type, created_at)
		(select id, user_id, inst_id, tipo_acceso, fecha 
		from ".$this->from_dbname.".omp_user_instances)\n";
		//echo $sql."\n";
		$updated=parent::executeUpdate($sql);
		echo "$updated omp_user_instances transfered!\n";

		$sql="truncate table ".$this->to_dbname.".omp_static_text";
		parent::executeQuery($sql);
		echo "omp_static_text in ".$this->to_dbname." truncated!\n";
		
		$sql="insert into ".$this->to_dbname.".omp_static_text (id, `key`, language, `value`, created_at)
		(select min(id), text_key, language, min(text_value), now() 
		from ".$this->from_dbname.".omp_static_text
		group by text_key, language)\n";
		//echo $sql."\n";
		$updated=parent::executeUpdate($sql);
		echo "$updated omp_static_text transfered!\n";
		
	}

	function __construct($conn_from, $conn_to, $params, $debug = false) {// requires doctrine dbal connection or array with data			
		foreach ($params as $key => $val) {
			$this->$key = $val;
		}
		return parent::__construct($conn_from, $conn_to);
	}

	function transform_values(&$value, $key) {
		//echo "$key $value \n";
		if ($key=='id'|| $key=='ordering' || $key=='rel_id' || $key=='atri_id' || $key=='rol_id'
		|| $key=='parent_class_id' || $key=='child_class_id' || $key=='class_id'
		|| $key=='tab_id' || $key=='fila' || $key=='columna' || $key=='row' || $key=='column'
		) 
		{
			$value = (int) $value;
		}
	}

	function get_roles() {
		$sql = "select * 
		from omp_roles 
		order by id";
		$rows = parent::fetchAll($sql);
		
		$result=array();
		foreach($rows as $row)
		{
			if ($this->from_version==4 && $this->to_version==5)
			{
				unset($row['enabled']);
				$row['name']=$row['rol_name'];
				unset($row['rol_name']);
			}
			
			$sql="select * from omp_roles_classes where rol_id=".$row['id'];
			$rows2=parent::fetchAll($sql);
			foreach ($rows2 as $row2)
			{
				if ($this->from_version==4 && $this->to_version==5)
				{
					$row2['permissions']=$row2['permisos'];
					unset($row2['permisos']);
				}
				$row['roles_classes'][]=$row2;
			}
					
			$result[]=$row;
		}
					
		array_walk_recursive($result, array($this, "transform_values"));
		return $result;
	}


	function set_roles($rows) {
		//print_r($rows);die;
		parent::truncate_table('omp_roles', 'conn_to');
		parent::truncate_table('omp_roles_classes', 'conn_to');
		$i=0;
		$j=1;
		foreach ($rows as $row)
		{
			$sql="insert into omp_roles (id, name)
			values (".$row['id'].", ".parent::escape($row['name'], 'conn_to').")";
			//echo $sql."\n";
			parent::insert_one($sql, 'conn_to');
			foreach ($row['roles_classes'] as $role_class)
			{
				$sql="insert into omp_roles_classes (id, class_id, rol_id, browseable, insertable, editable, deleteable, permissions, status1, status2, status3, status4, status5)
				values (".$role_class['id'].", ".parent::escape($role_class['class_id'], 'conn_to').", ".parent::escape($role_class['rol_id'], 'conn_to')."
				, ".parent::escape($role_class['browseable'], 'conn_to').", ".parent::escape($role_class['insertable'], 'conn_to').", ".parent::escape($role_class['editable'], 'conn_to')."
				, ".parent::escape($role_class['deleteable'], 'conn_to').", ".parent::escape($role_class['permissions'], 'conn_to')."
				, ".parent::escape($role_class['status1'], 'conn_to').", ".parent::escape($role_class['status2'], 'conn_to')."
				, ".parent::escape($role_class['status3'], 'conn_to').", ".parent::escape($role_class['status4'], 'conn_to')."
				, ".parent::escape($role_class['status5'], 'conn_to')."
				)";
				//echo $sql."\n";
				parent::insert_one($sql, 'conn_to');	
				$j++;
			}
			$i++;
		}
		echo "\n".($j-1)." roles_classes inserted\n";
		return $i;
	}

	
	function get_tabs() {
		$sql = "select * 
		from omp_tabs 
		order by id";
		$rows = parent::fetchAll($sql);
		
		$result=array();
		foreach($rows as $row)
		{
			if ($this->from_version==4 && $this->to_version==5)
			{
				$row['label_key']='omp_tab.'.$row['name'];
				unset($row['name']);
				unset($row['name_ca']);
				unset($row['name_es']);
				unset($row['name_en']);
			}
			$result[]=$row;
		}
					
		array_walk_recursive($result, array($this, "transform_values"));
		return $result;
	}	
	
	function set_tabs($rows) {
		parent::truncate_table('omp_tabs', 'conn_to');
		$i=0;
		foreach ($rows as $row)
		{
			$sql="insert into omp_tabs (id, label_key, ordering)
			values (".$row['id'].", ".parent::escape($row['label_key'], 'conn_to').", ".$row['ordering'].")";
			//echo $sql."\n";
			parent::insert_one($sql, 'conn_to');
			$i++;
		}
		return $i;
	}	

	function get_languages() {
		$sql = "select substring(min(id),1,1) id, language
		from omp_attributes 
		where language!='ALL'
		group by language order by id";
		$rows = parent::fetchAll($sql);

		array_walk_recursive($rows, array($this, "transform_values"));
		return $rows;
	}
	
	function set_languages($rows) {
		  $this->to_languages=$rows;
			return count($rows);
	}

	function get_users() {
		$sql = "select *
		from omp_users
		";
		
		$rows = parent::fetchAll($sql);
		$result=array();
		foreach($rows as $row)
		{
			if ($this->from_version==4 && $this->to_version==5)
			{
				$row['type']=$row['tipus'];
				unset($row['tipus']);
			}
			$result[]=$row;
		}
					
		array_walk_recursive($result, array($this, "transform_values"));
		return $result;
	}

	function set_users($rows) {
		parent::truncate_table('omp_users', 'conn_to');
		$i=0;
		//print_r($rows);die;
		foreach ($rows as $row)
		{
			$sql="insert into omp_users (id, username, password, complete_name
			, rol_id, language, type)
			values (".$row['id'].", ".parent::escape($row['username'], 'conn_to').", ".parent::escape($row['password'], 'conn_to').", ".parent::escape($row['complete_name'], 'conn_to')."
			, ".parent::escape($row['rol_id'], 'conn_to').", ".parent::escape($row['language'], 'conn_to').", ".parent::escape($row['type'], 'conn_to').")";
			//echo $sql."\n";
			parent::insert_one($sql, 'conn_to');
			$i++;
		}
		return $i;
	}	

	function get_class_groups() {
		$sql = "select *
		from omp_class_groups
		";
		$rows = parent::fetchAll($sql);

		$result=array();
		foreach($rows as $row)
		{
			if ($this->from_version==4 && $this->to_version==5)
			{
				$row['label_key']='omp_class_group.'.$row['caption'];
				unset($row['caption']);
				unset($row['caption_ca']);
				unset($row['caption_es']);
				unset($row['caption_en']);
			}
			
/*
 			$sql="select id from omp_classes where grp_id=".$row['id']." order by grp_order";
 
			$classes_in_group=parent::fetchAll($sql);
			foreach ($classes_in_group as $class_in_group)
			{
				$row['classes'][]=$class_in_group['id'];
			}
*/			
			$result[]=$row;
		}
					
		array_walk_recursive($result, array($this, "transform_values"));
		return $result;
	}
	
	function set_class_groups($rows) {
		parent::truncate_table('omp_class_groups', 'conn_to');
		$i=0;
		//print_r($rows);die;
		foreach ($rows as $row)
		{
			$sql="insert into omp_class_groups (id, label_key, ordering)
			values (".$row['id'].", ".parent::escape($row['label_key'], 'conn_to').", ".parent::escape($row['ordering'], 'conn_to').")";
			//echo $sql."\n";
			parent::insert_one($sql, 'conn_to');
			$i++;
		}
		return $i;
	}	

	function get_classes() {
		$result = array();
		$sql = "select *
		from omp_classes
		order by grp_id, grp_order
		";
		$rows = parent::fetchAll($sql);

		foreach ($rows as $row) {
			$id = $row['id'];
			if ($this->from_version==4 && $this->to_version==5)
			{
				$row['name']=strtolower($row['name']);
				unset($row['tag']);
				unset($row['name_ca']);
				unset($row['name_es']);
				unset($row['name_en']);
				$row['label_key']='omp_class.'.$row['name'];
				if ($this->minimal)
				{
					unset($row['description']);
				}
			}
			
			if ($this->from_version==4) $order_add=',fila, columna';
			if ($this->from_version==5) $order_add=',`row`, `column`';
			
			$sql = "select *
			from omp_class_attributes
			where class_id=$id
			order by tab_id $order_add";
			
			$class_attributes=array();
			$rows2=parent::fetchAll($sql);
			foreach($rows2 as $row2)
			{
				if ($this->from_version==4 && $this->to_version==5)
				{
					$row2['row']=$row2['fila'];
					$row2['column']=$row2['columna'];
					if ($row2['mandatory']=='Y')
					{
						$row2['params']['mandatory']=true;
					}
					else
					{
						$row2['params']['mandatory']=false;
					}
					unset($row2['fila']);
					unset($row2['columna']);
					unset($row2['mandatory']);
				}
				
				if (!($this->from_version==4 && $this->to_version==5 && $row2['atri_id']==1))
				{// afegim sempre el class attribute excepte si es el 1 (nom_intern) i estem migrant de 4 a 5
					$class_attributes[]=$row2;					
				}

			}
			
			
			$row['class_attributes'] = $class_attributes;
			$result[] = $row;
		}

		array_walk_recursive($result, array($this, "transform_values"));

		return $result;
	}
	
	function set_classes($rows) {
		parent::truncate_table('omp_classes', 'conn_to');
		parent::truncate_table('omp_class_attributes', 'conn_to');
		$i=0;
		$j=1;
		//print_r($rows);die;
		foreach ($rows as $row)
		{
			$sql="insert into omp_classes (id, name, description, label_key, grp_id, grp_order, recursive_clone)
				values (".$row['id'].", ".parent::escape($row['name'], 'conn_to').", ".parent::escape($row['description'], 'conn_to').", ".parent::escape($row['label_key'], 'conn_to')."
			, ".parent::escape($row['grp_id'], 'conn_to').", ".parent::escape($row['grp_order'], 'conn_to').", ".parent::escape($row['recursive_clone'], 'conn_to').")";
		//echo $sql."\n";
			$class_id=parent::insert_one($sql, 'conn_to');
			$sql="insert into omp_class_attributes (id, class_id, atri_id, rel_id, tab_id, `row`, `column`, params)
					values ";
			foreach ($row['class_attributes'] as $class_attribute)
			{
				if (!isset($class_attribute['id'])) $class_attribute['id']=$j;
				if (!isset($class_attribute['row'])) $class_attribute['row']=$j;
				if (!isset($class_attribute['column'])) $class_attribute['column']=1;
				$sql.="(".$class_attribute['id'].", ".$class_id.", ".parent::escape($class_attribute['atri_id'], 'conn_to').", ".parent::escape($class_attribute['rel_id'], 'conn_to').", ".parent::escape($class_attribute['tab_id'], 'conn_to')."
				, ".parent::escape($class_attribute['row'], 'conn_to').", ".parent::escape($class_attribute['column'], 'conn_to')."
				, ".parent::escape(json_encode($class_attribute['params']), 'conn_to')."),";
				//echo $sql."\n";
				echo '.';
				$j++;
			}
			$sql=substr($sql, 0, strlen($sql) - 1);
			parent::executeQuery($sql, 'conn_to');
			$i++;
		}
		echo "\n".($j-1)." class_attributes inserted\n";
		return $i;
	}	

	function transform_attribute_array_from4_to5($row) {
		
		if ($row['id']==1 && $row['name']=='nom_intern') return null;
		
		if (isset($row['lookup_id']) && $row['lookup_id'] != '') {
			$row['params'] = $this->lookups_params($row['lookup_id']);
		}
		unset($row['lookup_id']);
		
		if ($row['type']=='I')
		{
			if (isset($row['img_width']))
			{
				$row['params']['width']=$row['img_width'];
			}
			if (isset($row['img_height']))
			{
				$row['params']['height']=$row['img_height'];
			}
		}

		$row['type'] = $this->old_type_to_new_type($row['type']);
		
		unset($row['img_width']);
		unset($row['img_height']);		

		$row['name']=$row['tag'];
		$row['label_key']='omp_attribute.'.$row['tag'];
		unset($row['tag']);
		unset($row['caption']);
		unset($row['caption_ca']);
		unset($row['caption_es']);
		unset($row['caption_en']);

		if ($row['id'] > 10000) {
			$row['multilang'] = true;
		} else {
			$row['multilang'] = false;
		}

		return $row;
	}
	
	function transform_attribute_array_from5_to5minimal($row)
	{
		if ($row['language']!=$this->default_language && $row['multilang']) return null;
		unset($row['language']);
		if ($row['multilang'])
		{
			$row['id']=$row['id']%10000;
		}
		unset($row['description']);
		return $row;
	}

	function get_attributes() {
		$result = array();
		$sql = "select *
		from omp_attributes
		where language='ALL'
		";
		$rows = parent::fetchAll($sql);
		foreach ($rows as $row) {
			if ($this->from_version==4 && $this->to_version==5) {
				$row = $this->transform_attribute_array_from4_to5($row);
				if ($this->minimal)
				{
					$row=$this->transform_attribute_array_from5_to5minimal($row);
				}
			}
			if ($row) $result[] = $row;
		}

		$sql = "select * 
		from omp_attributes
		where id>=10000
		and language!='ALL'
		";
		$rows = parent::fetchAll($sql);
		foreach ($rows as $row) {
			if ($this->from_version==4 && $this->to_version==5) {
				$row = $this->transform_attribute_array_from4_to5($row);
				if ($this->minimal)
				{
					$row=$this->transform_attribute_array_from5_to5minimal($row);
				}
			}
			if ($row) $result[] = $row;
		}

		array_walk_recursive($result, array($this, "transform_values"));

		return $result;
	}
	
	function set_attributes($rows) {
		parent::truncate_table('omp_attributes', 'conn_to');
		$i=0;
		//print_r($rows);die;
		foreach ($rows as $row)
		{
			if (!isset($row['params']))
			{
				$params='null';
			}
			else
			{
				$params=parent::escape(json_encode($row['params']), 'conn_to');
			}
			$sql="insert into omp_attributes (id, name, description, `type`, params, language, label_key)
			values (".$row['id'].", ".parent::escape($row['name'], 'conn_to').", ".parent::escape($row['description'], 'conn_to').", ".parent::escape($row['type'], 'conn_to')."
			, $params, ".parent::escape($row['language'], 'conn_to').", ".parent::escape($row['label_key'], 'conn_to').")";
			//echo $sql."\n";
			parent::insert_one($sql, 'conn_to');
			$i++;
		}
		return $i;	
	}		

	function get_relations() {
		$sql = "select *
		from omp_relations
		";
		$rows = parent::fetchAll($sql);

		$result=array();
		foreach($rows as $row)
		{
			if ($this->from_version==4 && $this->to_version==5)
			{
				$row['name']=strtolower($row['name']);
				$row['label_key']='omp_relations.'.$row['name'];
				unset($row['tag']);
				unset($row['caption_ca']);
				unset($row['caption_es']);
				unset($row['caption_en']);
				if ($this->minimal)
				{
					unset($row['description']);
				}
			}
			$result[]=$row;
		}
					
		array_walk_recursive($result, array($this, "transform_values"));
		return $result;
	}
	
	function set_relations($rows) {
		parent::truncate_table('omp_relations', 'conn_to');
		$i=0;
		//print_r($rows);die;
		foreach ($rows as $row)
		{
			$sql="insert into omp_relations (id, name, label_key, description, parent_class_id, child_class_id, multiple_child_class_id)
			values (".$row['id'].", ".parent::escape($row['name'], 'conn_to').", ".parent::escape($row['label_key'], 'conn_to').", ".parent::escape($row['description'], 'conn_to')."
			, ".parent::escape($row['parent_class_id'], 'conn_to').", ".parent::escape($row['child_class_id'], 'conn_to').", ".parent::escape($row['multiple_child_class_id'], 'conn_to').")";
			//echo $sql."\n";
			parent::insert_one($sql, 'conn_to');
			$i++;
		}
		return $i;	
	}		

	function old_type_to_new_type($attribute_type) {
		if (stripos($attribute_type, "\\")) {
			return $attribute_type;
		} else {
			$mapping_array = array(
				'A' => '\Omatech\Editora\Attributes\TextAreaWYSIWYG'
				, 'C' => '\Omatech\Editora\Attributes\TextArea'
				, 'D' => '\Omatech\Editora\Attributes\Date'
				, 'F' => '\Omatech\Editora\Attributes\File'
				, 'G' => '\Omatech\Editora\Attributes\FlashFile'
				, 'I' => '\Omatech\Editora\Attributes\Image'
				, 'K' => '\Omatech\Editora\Attributes\TextAreaCKEditor'
				, 'L' => '\Omatech\Editora\Attributes\Lookup'
				, 'M' => '\Omatech\Editora\Attributes\GoogleMaps'
				, 'N' => '\Omatech\Editora\Attributes\Numeric'
				, 'O' => '\Omatech\Editora\Attributes\Color'
				, 'P' => '\Omatech\Editora\Attributes\ProtectedFile'
				, 'R' => '\Omatech\Editora\Attributes\Relation'
				, 'T' => '\Omatech\Editora\Attributes\TextAreaHTML'
				, 'U' => '\Omatech\Editora\Attributes\URL'
				, 'W' => '\Omatech\Editora\Attributes\APP'
				, 'X' => '\Omatech\Editora\Attributes\XML'
				, 'S' => '\Omatech\Editora\Attributes\SingleLineString'
				, 'Y' => '\Omatech\Editora\Attributes\SingleLineString'
				, 'Z' => '\Omatech\Editora\Attributes\URLNice'
			);

			if (array_key_exists($attribute_type, $mapping_array)) {
				return $mapping_array[$attribute_type];
			} else {
				return '\Omatech\Editora\Attributes\SingleLineString';
			}
		}
	}

	function lookups_params($id) {
		$sql = "select * 
		from omp_lookups_values
		where lookup_id=$id
		order by ordre";
		$rows = parent::fetchAll($sql);
		
		$result=array();
		foreach($rows as $row)
		{
			unset($row['caption']);
			unset($row['caption_ca']);
			unset($row['caption_es']);
			unset($row['caption_en']);
			unset($row['value_ca']);
			unset($row['value_es']);
			unset($row['value_en']);
			unset($row['lookup_id']);
			unset($row['ordre']);
			$result[]=$row;
		}
			
		
		array_walk_recursive($result, array($this, "transform_values"));
		return $result;
	}
	
	
	
	
	function transform_attributes_fromgenerator5_to5($input_array)
	{
		$languages=$input_array['languages'];

		$result_row=array();
		$result=array();
		foreach ($input_array['attributes'] as $row)
		{
			$id=$row[0];
			$result_row['id']=$id;
			$result_row['name']=$row[1];
			$result_row['label_key']='omp_attribute.'.$row[1];
			$result_row['description']="";
			$result_row['type']=$row[2];
			$result_row['params']=$row[3];
			if ($row[4])
			{// es multiidioma, hem de crear un per 
				foreach ($languages as $lang_id=>$language_code)
				{
					$result_row['id']=$id+$lang_id;
					$result_row['language']=$language_code;
					$result[]=$result_row;
				}
			}
			else 
			{// es language='ALL'
				$result_row['language']='ALL';
				$result[]=$result_row;
			}
			
		}	
		return $result;
	}

	
	function transform_relations_fromgenerator5_to5($input_array)
	{
		$result_row=array();
		$result=array();
		foreach ($input_array['relations'] as $row)
		{
			$id=$row[0];
			$result_row['id']=$id;
			$result_row['name']=$row[1];
			$result_row['label_key']='omp_relations.'.$row[1];
			$result_row['description']="";
			$result_row['parent_class_id']=$row[2];
			
			if (is_int($row[3]))
			{
				$result_row['child_class_id']=$row[3];
				$result_row['multiple_child_class_id']="";				
			}
			else
			{
				$result_row['child_class_id']=0;
				$result_row['multiple_child_class_id']=$row[3];								
			}
			$result[]=$result_row;		
		}	
		return $result;
	}
	
	
	function transform_classes_fromgenerator5_to5($input_array)
	{
		$result_row=array();
		$result=array();
		foreach ($input_array['classes'] as $row)
		{
			$id=$row[0];
			$result_row['id']=$id;
			$result_row['name']=$row[1];
			$result_row['label_key']='omp_class.'.$row[1];
			$result_row['description']="";
			$result_row['grp_id']=$row[2];
			$result_row['grp_order']=$row[3];
			$result_row['recursive_clone']='N';
			$result_row['class_attributes']=array();
			
			$attributes=explode(',', $row[4]);
			//print_r($attributes);
			foreach ($attributes as $atri_or_rel_id)
			{
				$atris_or_relations_array=$this->find_attribute_or_relation_in_editora5generator_arrays ($id, $atri_or_rel_id, $input_array);
				foreach ($atris_or_relations_array as $one_atri_or_relation)
				{
					$result_row['class_attributes'][]=$one_atri_or_relation;
				}
			}
			
			//print_r($result_row['class_attributes']);die;
							
			$result[]=$result_row;		
		}	
		return $result;
	}
	
	function find_attribute_or_relation_in_editora5generator_arrays ($class_id, $id, $input_array)
	{
		$languages=$input_array['languages'];
		
		$result=array();
		$result_row=array();
		$result_row['class_id']=$class_id;
		$result_row['atri_id']=0;
		$result_row['rel_id']=0;
		$result_row['params']['mandatory']=false;
		//echo "aqui id val $id\n";
		foreach ($input_array['attributes'] as $row)
		{
//echo "$class_id $id comparando con ".$row[0]."\n";
			if ($row[0]==$id)
			{
				if ($this->minimal)
				{
							$result_row['atri_id']=$id;
							$result_row['tab_id']=1;
							$result[]=$result_row;					
				}
				else {
					if ($row[4])
					{// es multiidioma, hem de crear un per 
						foreach ($languages as $lang_id=>$language_code)
						{
							$result_row['atri_id']=$id+$lang_id;
							$result_row['tab_id']=$lang_id;
							$result[]=$result_row;
						}
					}
					else 
					{// es language='ALL'
							$result_row['atri_id']=$id;
							$result_row['tab_id']=1;
							$result[]=$result_row;
					}
					
				}
			}
		}
		
		foreach ($input_array['relations'] as $row)
		{
			if ($row[0]==$id)
			{
				$result_row['rel_id']=$id;
				$result_row['tab_id']=1;
				$result[]=$result_row;
			}
		}		
		
		return $result;
	}
	
	function transform_class_groups_fromgenerator5_to5($input_array)
	{
		$result_row=array();
		$result=array();
		foreach ($input_array['class_groups'] as $key=>$val)
		{
			$result_row['id']=$val;
			$result_row['label_key']='omp_class_group.'.$key;
			$result_row['ordering']=$val;
			
			$result[]=$result_row;		
		}	
		return $result;
	}
		
	function transform_languages_fromgenerator5_to5($input_array)
	{
		$result_row=array();
		$result=array();
		foreach ($input_array['languages'] as $key=>$val)
		{
			$result_row['id']=$key/10000;
			$result_row['language']=$val;
			
			$result[]=$result_row;		
		}	
		return $result;
	}

	function transform_tabs_fromgenerator5_to5($input_array)
	{
		$result_row=array();
		$result=array();
		
		$i=1;
		$result_row['id']=$i;
		$result_row['label_key']='omp_tab.dades';
		$result_row['ordering']=$i;
		$result[]=$result_row;
		$i++;
		
		foreach ($input_array['languages'] as $key=>$val)
		{
			$result_row['id']=$key;
			$result_row['label_key']='omp_tab.'.$val;
			$result_row['ordering']=$i;
			
			$result[]=$result_row;
			$i++;
		}	
		return $result;
	}

	
	function transform_users_fromgenerator5_to5($input_array)
	{
		$result_row=array();
		$result=array();
		$i=1;
		foreach ($input_array['users'] as $row)
		{
			$result_row['id']=$i;
			$i++;
			$result_row['username']=$row[0];
			$result_row['password']=$row[1];
			$result_row['complete_name']=$row[2];
			$result_row['rol_id']=$row[3];
			$result_row['language']=$row[4];
			$result_row['type']=$row[5];
							
			$result[]=$result_row;		
		}	
		return $result;
	}
	
	
	function transform_roles_fromgenerator5_to5($input_array)
	{
		$result_row=array();
		$result=array();
		$i=1;

		$result_row['id']=1;
		$result_row['name']='admin';		
		foreach ($input_array['classes'] as $row)
		{
			$result_row['roles_classes'][]=$this->create_role_classe_row($i, $row[0], 1);
			$i++;
		}	
		$result[]=$result_row;		

		$result_row=array();
		$result_row['id']=2;
		$result_row['name']='user';		
		foreach ($input_array['classes'] as $row)
		{
			$result_row['roles_classes'][]=$this->create_role_classe_row($i, $row[0], 2);
			$i++;
		}	
		$result[]=$result_row;		


		return $result;
	}

	function create_role_classe_row($id, $class_id, $rol_id)
	{
		$one_role_class=array();
		$one_role_class['id']=$id;
		$one_role_class['class_id']=$class_id;
		$one_role_class['rol_id']=$rol_id;
		$one_role_class['browseable']='Y';
		$one_role_class['insertable']='Y';
		$one_role_class['editable']='Y';
		$one_role_class['deleteable']='Y';
		$one_role_class['permissions']='Y';
		$one_role_class['status1']='Y';
		$one_role_class['status2']='Y';
		$one_role_class['status3']='Y';
		$one_role_class['status4']='Y';
		$one_role_class['status5']='Y';
		return $one_role_class;
	}
	
	
	
	function transform_attributes_from5_togenerator5($input_array, $default_language='es'){
		$result_array=array();
		foreach ($input_array['attributes'] as $row)
		{
			if ($row['language']=='ALL')
			{
				$result_array[]=array($row['id'], $row['name'], $row['type'], $row['params'], false);
			}
			if ($row['language']==$default_language)
			{
				$result_array[]=array($row['id']%10000, $row['name'], $row['type'], $row['params'], true);
			}
		}
		return $result_array;
	}

	function transform_relations_from5_togenerator5($input_array){
		$result_array=array();
		foreach ($input_array['relations'] as $row)
		{
			if (isset($row['child_class_id']) && $row['child_class_id']>0)
			{
				$childs=$row['child_class_id'];
			}
			else
			{
				$childs=$row['multiple_child_class_id'];			
			}
			$result_array[]=array($row['id'], $row['name'], $row['parent_class_id'], $childs);
		}
		return $result_array;
	}

	function transform_classes_from5_togenerator5($input_array){
		$result_array=array();
		foreach ($input_array['classes'] as $row)
		{
			$class_attributes_array=array();
			foreach ($row['class_attributes'] as $class_attribute)
			{
				$atri_id=-1;
				if ($class_attribute['atri_id']>0) 
				{
					if ($class_attribute['atri_id']>10000)
					{// es multilang
						if (($class_attribute['atri_id']<20000))
						{// es del default_language
							$atri_id=$class_attribute['atri_id']%10000;
						}
						// sino el deixem a -1
					}
					else
					{// es language ALL
						$atri_id=$class_attribute['atri_id'];
					}
				}
				elseif ($class_attribute['rel_id']>0) 
				{
					$atri_id=$class_attribute['rel_id'];
				}

				if ($atri_id>0)
				{// nomes afegim l'atribut si te sentit (es una relacio o un atribut ALL o un multiidioma amb l'idioma per defecte (no afegim els multiidiomes d'altres idiomes
					$class_attributes_array[]=$atri_id;				
				}
			}
			$result_array[]=array($row['id'], $row['name'], $row['grp_id'], $row['grp_order'], implode(',', $class_attributes_array));
		}
		return $result_array;
	}

	function transform_class_groups_from5_togenerator5($input_array){
		$result_array=array();
		foreach ($input_array['class_groups'] as $row)
		{
			$result_array[substr($row['label_key'], stripos($row['label_key'], '.')+1)]=$row['id'];
		}
		return $result_array;
	}

	function transform_users_from5_togenerator5($input_array){	
		$result_array=array();
		foreach ($input_array['users'] as $row)
		{
			$result_array[]=array($row['username'], $row['password'], $row['complete_name'], $row['rol_id'], $row['language'], $row['type']);
		}
		return $result_array;
	}

	function transform_languages_from5_togenerator5($input_array){	
		$result_array=array();
		foreach ($input_array['languages'] as $row)
		{
			$result_array[$row['id']*10000]=$row['language'];
		}
		return $result_array;
	}		

	






	
}
