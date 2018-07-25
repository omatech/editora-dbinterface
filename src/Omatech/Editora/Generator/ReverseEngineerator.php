<?php

/**
 * Created by Omatech
 * Date: 26/04/18 12:24
 */

namespace Omatech\Editora\Generator;

use Omatech\Editora\DBInterfaceBase;

class ReverseEngineerator extends DBInterfaceBase {


	public function __construct($conn, $params=array()) {
		parent::__construct($conn, $params);
	}
	
  public function reverseEngineerEditora()
	{
		$return_array=array();
		$return_array['truncate_users']=false;
		$return_array['users']=$this->getUsersArray();
		$return_array['languages']=$this->getLanguagesArray();
		$return_array['groups']=$this->getGroupsArray();
		$return_array['classes']=$this->getClassesArray();
				
		$return_array['attributes_order_string']=$this->getAttributes ('B');
		$return_array['attributes_order_date']=$this->getAttributes ('E');
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
		
		$return_array['images_sizes']=$this->getImagesSizesArray();
		$return_array['relations']=$this->getRelationsArray();
		$return_array['relation_names']=$this->getRelationNamesArray();
		$return_array['attributes_classes']=$this->getAttributesClassesArray();
		return $return_array;
	}
	
	function quote($v)
	{
		if (!is_numeric($v))
		{
			$v="'".str_replace("'", "\'", $v)."'";
		}
		return $v;
	}
	
	public function aplanaClass($rows)
	{
		$return_string="[";
		foreach ($rows as $key=>$val)
		{
			if (!is_numeric($key)) $key="'$key'";
			$return_string.="$key => [".implode(',', array_map(array($this,'quote'), $val))."],\n\t\t";
		}
		$return_string=substr($return_string, 0, strlen($return_string)-4);
		$return_string.="\n\t\t],\n";
		return $return_string;				
	}
	
	public function twoLevelArrayToCode($rows)
	{
		$return_string="[";
		foreach ($rows as $key=>$val)
		{
			if (!is_numeric($key)) $key="'$key'";
			if (is_array($val))
			{
				$return_string.="\n\t$key => ".$this->aplanaClass($val)."\n";
			}
			else
			{
				if (!is_numeric($val)) $val="'$val'";
				$return_string.="\n\t$key => ".$val.",";				
			}
		}
		$return_string=substr($return_string, 0, strlen($return_string)-1);
		$return_string.="\n\t],\n";
		return $return_string;		
	}
	
	public function attributeArrayToCode($label, $rows, $remove_internal_keys=false)
	{
		if (!$rows) return;
		$return_string="'$label'=>[";
		if ($rows)
		{
			foreach ($rows as $key=>$val)
			{
				if (!is_numeric($key)) $key="'$key'";
				if ($remove_internal_keys)
				{
					$return_string.="\n\t[".implode(',', array_map(array($this,'quote'), $val))."],\t\t";
				}
				else
				{
					$return_string.="\n\t$key => [".implode(',', array_map(array($this,'quote'), $val))."],\t\t";					
				}
			}
			$return_string=substr($return_string, 0, strlen($return_string)-3);
		}
		$return_string.="\n\t],\n";
		return $return_string;		
	}

	public function simpleArrayToCode($rows)
	{
		$return_string="[";
		foreach ($rows as $key=>$val)
		{
			if (!is_numeric($key)) $key="'$key'";
			if (!is_numeric($val)) $val="'$val'";
			$return_string.="\n\t$key => $val,";
		}
		$return_string=substr($return_string, 0, strlen($return_string)-1);
		$return_string.="\n\t],\n";
		return $return_string;
	}
	
	public function arrayToCode ($rows)
	{
		$return_string="<?php\n".'$data'." = [\n";
		foreach ($rows as $key=>$val)
		{
			if ($key=='truncate_users')
			{
				if ($val===true)
				{
					$return_string.="'truncate_users'=>true,\n";					
				}
				else
				{
					$return_string.="'truncate_users'=>false,\n";					
				}
			}
			elseif ($key=='users')
			{
				$return_string .= $this->attributeArrayToCode('users',$val,true);
			}
			elseif ($key=='languages')
			{
				$return_string .= "'languages'=>".$this->simpleArrayToCode($val);
			}
			elseif ($key=='groups')
			{
				$return_string .= "'groups'=>".$this->simpleArrayToCode($val);
			}
			elseif ($key=='classes')
			{
				$return_string .= "'classes'=>".$this->twoLevelArrayToCode($val);
			}			
			elseif ($key=='attributes_order_string')
			{
				$return_string .= $this->attributeArrayToCode('attributes_order_string', $val);
			}
			elseif ($key=='attributes_order_date')
			{
				$return_string .= $this->attributeArrayToCode('attributes_order_date', $val);
			}
			elseif ($key=='attributes_string')
			{
				$return_string .= $this->attributeArrayToCode('attributes_string', $val);
			}
			elseif ($key=='attributes_textarea')
			{
				$return_string .= $this->attributeArrayToCode('attributes_textarea', $val);
			}
			elseif ($key=='attributes_text')
			{
				$return_string .= $this->attributeArrayToCode('attributes_text', $val);
			}
			elseif ($key=='attributes_date')
			{
				$return_string .= $this->attributeArrayToCode('attributes_date', $val);
			}
			elseif ($key=='attributes_num')
			{
				$return_string .= $this->attributeArrayToCode('attributes_num', $val);
			}
			elseif ($key=='attributes_geolocation')
			{
				$return_string .= $this->attributeArrayToCode('attributes_geolocation', $val);
			}
			elseif ($key=='attributes_url')
			{
				$return_string .= $this->attributeArrayToCode('attributes_url', $val);
			}
			elseif ($key=='attributes_file')
			{
				$return_string .= $this->attributeArrayToCode('attributes_file', $val);
			}
			elseif ($key=='attributes_video')
			{
				$return_string .= $this->attributeArrayToCode('attributes_video', $val);
			}
			elseif ($key=='attributes_image')
			{
				$return_string .= $this->attributeArrayToCode('attributes_image', $val);
			}
			elseif ($key=='attributes_lookup')
			{
				$return_string .= $this->attributeArrayToCode('attributes_lookup', $val);
			}
			
			elseif ($key=='attributes_multi_lang_string')
			{
				$return_string .= $this->attributeArrayToCode('attributes_multi_lang_string', $val);
			}
			elseif ($key=='attributes_multi_lang_textarea')
			{
				$return_string .= $this->attributeArrayToCode('attributes_multi_lang_textarea', $val);
			}			
			elseif ($key=='attributes_multi_lang_file')
			{
				$return_string .= $this->attributeArrayToCode('attributes_multi_lang_file', $val);
			}
			elseif ($key=='attributes_multi_lang_image')
			{
				$return_string .= $this->attributeArrayToCode('attributes_multi_lang_image', $val);
			}
			elseif ($key=='lookups')
			{
				$return_string .= "'lookups'=>".$this->twoLevelArrayToCode($val);
			}			
			elseif ($key=='images_sizes')
			{
				$return_string .= "'images_sizes'=>".$this->twoLevelArrayToCode($val);
			}				
			elseif ($key=='relations')
			{
				$return_string .= "'relations'=>".$this->twoLevelArrayToCode($val);
			}				
			elseif ($key=='relation_names')
			{
				$return_string .= $this->attributeArrayToCode('relation_names', $val);
			}				
			elseif ($key=='attributes_classes')
			{
				$return_string .= "'attributes_classes'=>".$this->twoLevelArrayToCode($val);
			}				
		}
		$return_string=substr($return_string, 0, strlen($return_string)-2);
		$return_string.="\n];\n";
		return $return_string;
	}
	
	public function getRelationsArray()
	{
		$return_array=array();
		$sql="select * 
		from omp_relations
		order by id
		";
		$rows=$this->conn->fetchAll($sql);
		if ($rows)
		{
			foreach ($rows as $row)
			{
				if (isset($row['multiple_child_class_id']) && !empty($row['multiple_child_class_id']))
				{// we have at least one image size
					$return_array[$row['id']]=$row['parent_class_id'].','.$row['multiple_child_class_id'];				
				}
				else
				{
					$return_array[$row['id']]=$row['parent_class_id'].','.$row['child_class_id'];				
				}
			}
		}
		return $return_array;
	}
	
	public function getRelationNamesArray()
	{
		$return_array=array();
		$sql="select * 
		from omp_relations
		order by id
		";
		$rows=$this->conn->fetchAll($sql);
		if ($rows)
		{
			foreach ($rows as $row)
			{
				$return_array[$row['id']]=array($row['caption'],$row['tag']);						
			}
		}
		return $return_array;
	}	
	
	public function getAttributesClassesArray()
	{
		$return_array=array();
		$sql="select * from (
		select 0 es_rel, ca.* 
		from omp_class_attributes ca
		where atri_id is not null
		and atri_id>1
		and tab_id<10001
		) t
		order by class_id, es_rel, tab_id, fila, columna
		";
		$rows=$this->conn->fetchAll($sql);
		$class_id=$rows[0]['class_id'];
		$atris='';
		foreach ($rows as $row)
		{
			//echo $class_id.' '.$row['class_id'].' '.$row['tab_id'].' '.$row['atri_id'].' '.$row['rel_id']."\n";
			if ($class_id!=$row['class_id'])
			{
				$return_array[$class_id]=substr($atris,0,strlen($atris)-1);
				$atris='';
				$class_id=$row['class_id'];
				//print_r($return_array);
			}
			$resta=0;
			$tab_id=$row['tab_id'];
			if ($tab_id>=10000) $resta=$tab_id;
			if (isset($row['atri_id'])) $atri_id=$row['atri_id']-$resta;
			if (isset($row['rel_id'])) $atri_id=$row['rel_id'];
			if ($atri_id>1)
			{
				if ($row['columna']==2)
				{
					$atris.=substr($atris,0,strlen($atris)-1)."-$atri_id,";
				}
				else
				{
					$atris.="$atri_id,";					
				}
			}
			//echo $atris."\n";
			//print_r($return_array);
		}
		$return_array[$class_id]=substr($atris,0,strlen($atris)-1);
//print_r($return_array);die;		
		return $return_array;
	}
	
	public function getImagesSizesArray()
	{
		$return_array=array();
		$sql="select id, img_width, img_height 
		from omp_attributes
		where type='I'
		order by id
		";
		$rows=$this->conn->fetchAll($sql);
		if ($rows)
		{
			foreach ($rows as $row)
			{
				if (isset($row['img_width']) || isset($row['img_height']))
				{// we have at least one image size
					$size_string='x';
					if (isset($row['img_width']) && !empty($row['img_width']))
					{
						$size_string=$row['img_width'].$size_string;
					}
					if (isset($row['img_height']) && !empty($row['img_height']))
					{
						$size_string=$size_string.$row['img_height'];
					}
					$return_array[$row['id']]=$size_string;				
				}

			}
		}
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
				$return_array[$row['caption']]=$row['id'];
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
				//$element_array=array();
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
				//$element_array[$row['caption']]=$classes_array;
					
				$return_array[$row['caption']]=$classes_array;
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
	

