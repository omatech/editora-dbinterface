<?php

/**
 * Created by Omatech
 * Date: 26/04/18 12:24
 */

namespace Omatech\Editora\Generator;

use Omatech\Editora\DBInterfaceBase;

class ReverseEngineerator extends DBInterfaceBase {


	public function __construct($conn, $params) {
		parent::__construct($conn, $params);
	}
	
  public function reverseEngineerEditora()
	{
		$return_array=array();
		$return_array['truncate_users']=true;
		$return_array['users']=$this->getUsersArray();
		$return_array['languages']=$this->getLanguagesArray();
		$return_array['groups']=$this->getGroupsArray();
		$return_array['classes']=$this->getClassesArray();
				
		$return_array['attributes_string']=$this->getAttributes ('S');
		$return_array['attributes_textarea']=$this->getAttributes ('K');
		$return_array['attributes_text']=$this->getAttributes ('T');
		$return_array['attributes_date']=$this->getAttributes ('D');
		$return_array['attributes_num']=$this->getAttributes ('N');
		$return_array['attributes_geolocation']=$this->getAttributes ('M');
		$return_array['attributes_url']=$this->getAttributes ('U');		
		$return_array['attributes_file']=$this->getAttributes ('F');
		$return_array['attributes_video']=$this->getAttributes ('Y');
		$return_array['attributes_image']=$this->getAttributes ('I');
		$return_array['attributes_lookup']=$this->getAttributes ('L');

		$return_array['lookups']=$this->getLookups();
		
		$return_array['attributes_multi_lang_string']=$this->getAttributes ('S', true);
		$return_array['attributes_multi_lang_textarea']=$this->getAttributes ('K', true);
		$return_array['attributes_multi_lang_file']=$this->getAttributes ('F', true);
		$return_array['attributes_multi_lang_image']=$this->getAttributes ('I', true);
		
		return $return_array;
	}
	
	public function getLanguagesArray()
	{
		$return_array=array();
		$sql="select id, name 
		from omp_tabs
		where id>=10000
		order by id
		";
		$rows=$this->conn->fetchAll($sql);
		if ($rows)
		{
			foreach ($rows as $row)
			{
				$return_array[$row['id']]=$row['name'];
			}
		}
		return $return_array;
	}
	
	
	public function getUsersArray ()
	{
		$return_array=array();
		$sql="select id, username, complete_name, language, rol_id, tipus 
		from omp_users
		order by id
		";
			
		$rows=$this->conn->fetchAll($sql);
		if ($rows)
		{
			foreach ($rows as $row)
			{
				$element_array=array();
				$element_array[]=$row['username'];
				$element_array[]=$row['complete_name'];
				$element_array[]=$row['language'];
				$element_array[]=$row['rol_id'];
				$element_array[]=$row['tipus'];
				$return_array[$row['id']]=$element_array;
			}
		}
		return $return_array;
	}
	
	public function getGroupsArray ()
	{
		$return_array=array();
		$sql="select id, caption
		from omp_class_groups
		order by id
		";
			
		$rows=$this->conn->fetchAll($sql);
		if ($rows)
		{
			foreach ($rows as $row)
			{
				$element_array=array();
				$element_array[$row['caption']]=$row['id'];
				$return_array[]=$element_array;
			}
		}
		return $return_array;
	}	

	
	public function getClassesArray ()
	{
		$return_array=array();
		$sql="select id, caption
		from omp_class_groups
		order by id
		";
			
		$rows=$this->conn->fetchAll($sql);
		if ($rows)
		{
			foreach ($rows as $row)
			{
				$element_array=array();
				$classes_array=array();
				$sql="select * 
				from omp_classes c
				where c.grp_id=".$row['id']."
				order by grp_order
				";
				$rows_classes=$this->conn->fetchAll($sql);
				foreach ($rows_classes as $class)
				{
					$classes_array[$class['id']]=array($class['tag'], $class['name_ca']);
				}	
				$element_array[$row['caption']]=$classes_array;
					
				$return_array[]=$element_array;
			}
		}
		return $return_array;
	}	


  public function getAttributes ($type, $multilang=false)
	{
		$return_array=array();
		$sql_multilang_condition=" and a.id < 10000 ";
		if ($multilang)
		{
			$sql_multilang_condition=" and a.id >= 10000 and a.id <20000 ";
		}
		
		$sql="select id, tag, caption_ca, caption_es, caption_en, type, lookup_id
		from omp_attributes a
		where type='$type'
		and id>1
		$sql_multilang_condition
		";
		$rows=$this->conn->fetchAll($sql);
		foreach ($rows as $row)
		{
			$id=$row['id'];
			if ($multilang) $id=substr($id,2);
			$tag=$row['tag'];
			if ($row['lookup_id']) $tag="$tag,".$row['lookup_id'];
			$return_array[$id]=array($tag, $row['caption_ca'], $row['caption_es'], $row['caption_en']);
		}
		return $return_array;
	}

	
	public function getLookups ()
	{
		$return_array=array();
		$sql="select id, name from omp_lookups";
		$rows=$this->conn->fetchAll($sql);
		foreach ($rows as $row)
		{
			$lookup_id=$row['id'];
			$lookup_name=$row['name'];
			$sql="select * from omp_lookups_values where lookup_id=$lookup_id order by ordre";
			$values=$this->conn->fetchAll($sql);
			$values_array=array();
			foreach ($values as $value)
			{
				$values_array[$value['id']]=array($value['value'], $value['caption_ca'], $value['caption_es'], $value['caption_en']);
			}
			$return_array["$lookup_id,$lookup_name"]=$values_array;
		}
		return $return_array;
	}

	
}
	

