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
		$rows=$this->conn->fetchAssoc($sql);
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
		
	
		$rows=$this->conn->fetchAssoc($sql);
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

}
	

