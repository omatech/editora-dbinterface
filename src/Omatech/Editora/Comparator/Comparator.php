<?php

//à

namespace Omatech\Editora\Comparator;
use \Omatech\Editora\DBInterfaceBase;

class Comparator extends DBInterfaceBase {
	
  private $tables_to_check=[];
  private $errors=0;

  function __construct($conn_from, $conn_to, $params, $debug = false) {			
		foreach ($params as $key => $val) {
			$this->$key = $val;
		}
    
    $this->tables_to_check=[
      'omp_attributes'
      , 'omp_class_groups'
      , 'omp_relations'
      , 'omp_roles'
      , 'omp_users'];

    return parent::__construct($conn_from, $params);
	}

	function compare()
	{
    $this->compareNumRows();
    $this->compareRecords();
    $this->compareRolesClasses();
    $this->compareRelations();
    $this->compareRelationClasses();
    $this->compareAttributes();
    $this->compareLookups();
    if ($this->errors==0)
    {
      echo "We've reached the end of all compare procedures, it seems OK!\n";
      echo "Hurrey!\n";
    }
    else
    {
      echo "There are ".$this->errors." errors detected, please check them in order to validate the process!\n";
    }
	}
  
  private function compareNumRows()
  {
    foreach ($this->tables_to_check as $table)
    {
      $sql="select count(*) num from ".$this->from_dbname.".$table";
      $num_from=$this->fetchColumn($sql);
      $sql="select count(*) num from ".$this->to_dbname.".$table";
      $num_to=$this->fetchColumn($sql);
      echo "Comparing $table source=$num_from destination=$num_to \n";
      if ($num_from!=$num_to) 
      {
        $this->errors++;
        echo ("Error detected!\n");  
      }
    }    
  }

  private function compareRecords()
  {
    foreach ($this->tables_to_check as $table)
    {
      $sql="select * from ".$this->from_dbname.".$table";
      $from_records=$this->fetchAll($sql);
      $sql="select * from ".$this->to_dbname.".$table";
      $to_records=$this->fetchAll($sql);
      echo "Comparing records in $table ...";
      $res = $this->getRecordErrors($from_records, $to_records);
      if ($res) 
      {
        echo ("\nErrors detected!\n".$this->print_errors($res)."\n");
      }
      else 
      {
        echo " OK!\n";  
      }
    }    
  }	

  private function getRecordErrors($array1, $array2) {
    $errors = [];

    foreach($array1 as $key => $val) {
      $source_id="<comparing query>";
      if (isset($array1[$key]['id']))
      {
        $source_id=$array1[$key]['id'];
      }
      if (array_key_exists($key, $array2))
      {
        if ($val!==$array2[$key])
        {
          foreach ($array1[$key] as $field=>$field_value)
          {
            if ($array2[$key][$field]!==$field_value)
            {
              $errors[]="Field $field with ID=$source_id in source has value $field_value and in destination has ".$array2[$key][$field];
              $this->errors++;
            }
          }
        }
        unset($array2[$key]);
      }
      else
      {
        $errors[]="Key $key in source has no correspondence in destination\n";
        $this->errors++;
      }
    }

    foreach ($array2 as $key=>$val)
    {
      $errors[]="Key $key in destination has no correspondence in source\n";
      $this->errors++;
    }

    return $errors;
  }

  private function print_errors ($errors)
  {
    if (!$errors) return;
    echo count($errors)." errors detected:\n";
    foreach ($errors as $error)
    {
      echo "$error\n";
    }
  }

  private function compareQuery ($alias, $sql)
  {
    $from_records=$this->fetchAll(str_replace(':schema', $this->from_dbname, $sql));
    $to_records=$this->fetchAll(str_replace(':schema', $this->to_dbname, $sql));
    echo "Comparing records in $alias ...";
    $res = $this->getRecordErrors($from_records, $to_records);
    if ($res) 
    {
      echo ("\nErrors detected!\n".$this->print_errors($res)."\n");
    }
    else 
    {
      echo " OK!\n";  
    }

  }

  private function compareRolesClasses ()
  {
    $sql="select r.rol_name, c.tag, browseable, insertable, editable, deleteable, permisos, status1, status2, status3, status4, status5 
    from :schema.omp_roles_classes rc
    , :schema.omp_roles r
    , :schema.omp_classes c
    where rc.class_id=c.id
    and rc.rol_id=r.id
    order by r.rol_name, c.tag";

    return $this->compareQuery('roles_classes', $sql);
  }

  private function compareRelations ()
  {
    $sql="select c.tag, r.tag, r.name, r.caption, r.language, r.order_type, r.join_icon, r.create_icon, r.join_massive, r.autocomplete
    from :schema.omp_class_attributes ca
    , :schema.omp_relations r
    , :schema.omp_classes c
    , :schema.omp_tabs t
    where ca.class_id=c.id
    and ca.rel_id=r.id
    and ca.tab_id=t.id
    order by t.id, c.id, ca.fila, ca.columna, r.tag";
    return $this->compareQuery('relations', $sql);
  }

  private function compareAttributes ()
  {
    $sql="select c.tag, a.tag, a.name, a.type, a.lookup_id, a.img_width, a.img_height, a.language, a.caption_ca, a.caption_es, a.caption_en
    from :schema.omp_class_attributes ca
    , :schema.omp_attributes a
    , :schema.omp_classes c
    , :schema.omp_tabs t
    where ca.class_id=c.id
    and ca.rel_id=a.id
    and ca.tab_id=t.id
    order by t.id, c.id, ca.fila, ca.columna, a.tag";
    return $this->compareQuery('attributes', $sql);
  }

  private function compareRelationClasses ()
  {
    $sql="select tag, parent_class_tag, child_class_tag 
    from 
    (
    select r.id, r.tag, parent_c.tag parent_class_tag, child_c.tag child_class_tag, child_c.id child_id
    from :schema.omp_relations r
    , :schema.omp_classes parent_c
    , :schema.omp_classes child_c
    where r.parent_class_id=parent_c.id
    and r.child_class_id=child_c.id
    and r.child_class_id is not null
    union
    select r.id, r.tag, parent_c.tag, child_c.tag, child_c.id child_id
    from :schema.omp_relations r
    , :schema.omp_classes parent_c
    , :schema.omp_classes child_c
    where r.parent_class_id=parent_c.id
    and find_in_set (child_c.id, r.multiple_child_class_id)
    and r.multiple_child_class_id is not null
    ) t
    order by t.id, t.child_id";
    return $this->compareQuery('relationClasses', $sql);
  }

  private function compareLookups ()
  {
    $sql="select l.name, l.type, lv.ordre, lv.value, lv.caption_es, lv.caption_en, lv.caption_ca, dv.value default_value 
    from :schema.omp_lookups l left join :schema.omp_lookups_values dv on (l.default_id=dv.id)
    , :schema.omp_lookups_values lv
    where l.id=lv.lookup_id
    order by l.name, lv.ordre";

    return $this->compareQuery('Lookups', $sql);
  }
	
}
