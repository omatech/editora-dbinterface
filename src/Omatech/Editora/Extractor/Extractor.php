<?php

namespace Omatech\Editora\Extractor;

use \Omatech\Editora\DBInterfaceBase;

class Extractor extends DBInterfaceBase {

	protected $lang = 'ALL';
	protected $default_limit = 10000;
	protected $metadata = false;
	protected $extract_values = true;
	protected $paginator = null;

	///
	/// MAIN FUNCTIONS
	///
	


	public function findInstanceById($inst_id, $params = null, callable $callback = null) {
		$this->debug("Extractor::findInstanceById inst_id=$inst_id\n");

		$result = $this->getExtractionFromCache($params);
		// if we get the info from cache let's return
		if ($result)
			return $result;

		$sql = $this->sql_select_instances . "  
					from omp_instances i 
					, omp_classes c
					where 1=1
					and i.id=$inst_id
					and c.id=i.class_id
					" . $this->getPreviewFilter() . "
					limit 1
					";

		$this->debug("SQL a findInstanceById\n");
		$this->debug($sql);
		$row = $this->conn->fetchAssoc($sql);
		if (!$row)
			return array();

		$result = $this->prepareInstanceResultStructure($row, $params, $callback);

		// put info into the cache before returning
		$this->putInExtractionCache($result, $params);

		return $result;
	}

	public function findInstancesInClass($class, $num = null, $params = null, callable $callback = null) {
// $params['order'] = order class instances by order criteria, update_date|publishing_begins|inst_id|key_fields|order_date|order_string default publishing_begins
// $params['order_direction'] = direction of the order by clause, desc|asc defaults to asc
		$start = microtime(true);
		$this->debug("Extractor::findInstancesInClass class=$class num=$num\n");
		
		$result = $this->getExtractionFromCache($params);
		// if we get the info from cache let's return
		if ($result)
			return $result;
		
		$class_filter = $this->getClassFilter($class);
		if (isset($params['order'])) {
			$order_filter = $this->getOrderFilter($params['order'], $params['order_direction']);
		} else {// si no tenemos order ordenamos por los publicados recientemente
			$order_filter = $this->getOrderFilter('publishing_begins', 'desc');
		}
		$preview_filter = $this->getPreviewFilter();

		$this->setPagination($num, $class_filter, $preview_filter, $order_filter);
		$sql = $this->sql_select_instances . "  
					from omp_instances i 
					, omp_classes c
					where 1=1
					$class_filter
					and c.id=i.class_id
					$preview_filter
				  $order_filter
					" . $this->getLimitFilter($num) . "
					";



		$this->debug("SQL a findInstancesInClass\n");
		$this->debug($sql);
//$row=Model::get_one($sql);
		$rows = $this->conn->fetchAll($sql);
		$result = array();
		foreach ($rows as $row) {
			$result[] = $this->prepareInstanceResultStructure($row, $params, $callback);
		}
		
		// put info into the cache before returning
		$this->putInExtractionCache($result, $params);		
		return $result;
	}

	public function findInstancesInList($inst_ids, $num = null, $class = null, $params = null, callable $callback = null) {
		$start = microtime(true);
		$this->debug("Extractor::getInstanceList class=$class inst_ids=$inst_ids\n");
		
		$result = $this->getExtractionFromCache($params);
		// if we get the info from cache let's return
		if ($result)
			return $result;
		
		$class_filter = $this->getClassFilter($class);
		$order_filter = " order by FIELD(i.id, " . $inst_ids . ") ";

		$preview_filter = $this->getPreviewFilter();
		$ids_filter = $this->getIDsListFilter($inst_ids);
		$this->setPagination($num, $class_filter, $preview_filter, $order_filter, $ids_filter);

		$sql = $this->sql_select_instances . "  
			from omp_instances i 
			, omp_classes c
			where 1=1
			$class_filter
			and c.id=i.class_id
			$preview_filter
			$ids_filter
			$order_filter
			" . $this->getLimitFilter($num) . "
			";

		$this->debug("SQL a findInstancesInList\n");
		$this->debug($sql);
//$row=Model::get_one($sql);
		$rows = $this->conn->fetchAll($sql);
		$result = array();
		foreach ($rows as $row) {
			$result[] = $this->prepareInstanceResultStructure($row, $params, $callback);
		}
		
		// put info into the cache before returning
		$this->putInExtractionCache($result, $params);		
		return $result;
	}

	public function findInstancesBySearch($query, $num = null, $class = null, $params = null, callable $callback = null) {
		$start = microtime(true);
		$this->debug("Extractor::getInstanceList class=$class inst_ids=$inst_ids\n");
		
		$result = $this->getExtractionFromCache($params);
		// if we get the info from cache let's return
		if ($result)
			return $result;
		
		$class_filter = $this->getClassFilter($class);
		$order_filter = " order by relevance ";
		$search_filter = $this->getSearchFilter($query);

		$preview_filter = $this->getPreviewFilter();
		$ids_filter = $this->getIDsListFilter($inst_ids);
		$this->setPagination($num, $class_filter, $preview_filter, $order_filter, null, $search_filter);

		$sql = $this->sql_select_instances . " , MATCH (s.text) AGAINST ('" . $query . "') relevance
			from omp_search s
			, omp_instances i
			where 1=1
			$search_filter
			$class_filter
				and s.inst_id=i.id
			$preview_filter
			" . $this->getLimitFilter($num) . "
			$order_filter
			";

		$this->debug("SQL a findInstancesBySearch\n");
		$this->debug($sql);
//$row=Model::get_one($sql);
		$rows = $this->conn->fetchAll($sql);
		$result = array();
		foreach ($rows as $row) {
			$result[] = $this->prepareInstanceResultStructure($row, $params, $callback);
		}
		// put info into the cache before returning
		$this->putInExtractionCache($result, $params);		
		return $result;
	}

	public function findRelatedInstances($inst_id, $relation, $num = null, $params = null, callable $callback = null) {
		$this->debug("Extractor::findRelatedInstances inst_id=$inst_id relation=$relation\n");	

		if (isset($params['direction'])) {
			$direction = $params['direction'];
		} else {
			$direction = $this->getRelationDirection($inst_id, $relation);
		}
		$this->debug("direction $direction\n");

		if ($direction == 'child') {
			return $this->findChildrenInstances($inst_id, $relation, $num, $params, $callback);
		}
		if ($direction == 'parent') {
			return $this->findParentInstances($inst_id, $relation, $num, $params, $callback);
		}
		return array();
	}

	public function findChildrenInstances($inst_id, $relation, $num = null, $params = null, callable $callback = null) {
		$start = microtime(true);
		$this->debug("Extractor::findChildInstances\n");
		$this->debug("relation=$relation inst_id=$inst_id\n");

		$relation_row = $this->findChildRelation($relation, $inst_id);
		$rel_id = $relation_row['id'];
		if (!$rel_id)
			return array();

		if (isset($params['alias'])) {
			$tag = $params['alias'];
		} else {
			$tag = $relation_row['tag'];
		}

		$sql = $this->sql_select_instances . " , ri.weight relation_instance_weight
				from omp_relation_instances ri
				, omp_instances i
				, omp_classes c
				where ri.rel_id=$rel_id
				and ri.parent_inst_id=$inst_id
			  and ri.child_inst_id=i.id
				" . $this->getPreviewFilter() . "
				and i.class_id=c.id
				order by weight
				" . $this->getLimitFilter($num) . "
				";


		$this->debug("SQL a findChildInstances\n");
		$this->debug($sql);

		$rows = $this->conn->fetchAll($sql);

//print_r($rows);die;
		$result = array();

		foreach ($rows as $row) {
			$result[$tag]['instances'][] = $this->prepareInstanceResultStructure($row, $params, $callback);
		}
		if ($this->metadata) {
			$result[$tag]['metadata'] = $relation_row;
			$result[$tag]['metadata']['direction'] = 'child';
			if ($this->timings) {
				$end = microtime(true);
				$result[$tag]['metadata']['start'] = $start;
				$result[$tag]['metadata']['end'] = $end;
				$result[$tag]['metadata']['seconds'] = $end - $start;
			}
		}
		return $result;
	}

	public function findParentInstances($inst_id, $relation, $num = null, $params = null, callable $callback = null) {
		$start = microtime(true);
		$this->debug("Extractor::findParentInstances\n");
		$this->debug("inst_id=$inst_id relation=$relation\n");

		$relation_row = $this->findParentRelation($relation, $inst_id);
		$rel_id = $relation_row['id'];
		if (!$rel_id)
			return array();

		if (isset($params['alias'])) {
			$tag = $params['alias'];
		} else {
			$tag = $relation_row['tag'];
		}

		$sql = $this->sql_select_instances . "  , ri.weight relation_instance_weight
				from omp_relation_instances ri
				, omp_instances i
				, omp_classes c
				where ri.rel_id=$rel_id
				and ri.child_inst_id=$inst_id
			  and ri.parent_inst_id=i.id
				" . $this->getPreviewFilter() . "
				and i.class_id=c.id
				order by weight
				" . $this->getLimitFilter($num) . "
				";

		$this->debug("SQL a findParentInstances\n");
		$this->debug($sql);
//$row=Model::get_one($sql);
		$rows = $this->conn->fetchAll($sql);
//print_r($rows);die;
		$result = array();

		foreach ($rows as $row) {
			$result[$tag]['instances'][] = $this->prepareInstanceResultStructure($row, $params, $callback);
		}

		if ($this->metadata) {
			$result[$tag]['metadata'] = $relation_row;
			$result[$tag]['metadata']['direction'] = 'parent';
			if ($this->timings) {
				$end = microtime(true);
				$result[$tag]['metadata']['start'] = $start;
				$result[$tag]['metadata']['end'] = $end;
				$result[$tag]['metadata']['seconds'] = $end - $start;
			}
		}

		return $result;
	}

/// 
/// ACCESSORI FUNCTIONS
///

	private function clearExtractionCache($key)
	{
		$memcache_key = $this->conn->getDatabase() . ":extractor_cache:$cache_key:$this->lang";
		$this->deleteCache($memcache_key);
	}

	private function getExtractionFromCache($params) {// returns the object if found or false otherwise
		if (isset($params['extraction_cache_key'])) {
			$cache_key = $params['extraction_cache_key'];

			$memcache_key = $this->conn->getDatabase() . ":extractor_cache:$cache_key:$this->lang";
			$this->debug("MEMCACHE:: using key $memcache_key extraction\n");
			if (!$this->avoid_cache) {
				$this->debug("CACHE:: avoid_cache desactivado\n");
				if (!$this->preview) {// si no estem fent preview, mirem si esta activada la memcache i si existeix la key
					$this->debug("CACHE:: preview desactivado\n");
					if ($this->setupCache()) {
						$this->debug("CACHE:: setupCache OK\n");
						$result = $this->mc->get($memcache_key);
						if ($result) {
							$this->debug("CACHE:: HIT! VALUE=\n");
							$this->debug($result);
							$this->debug("\nCACHE:: END VALUE=\n");
							return $result;
						} else {
							return false;
						}
					}
				}
			}
		}
	}

	private function putInExtractionCache($result, $params) {
		if (isset($params['extraction_cache_key'])) {
			$cache_key = $params['extraction_cache_key'];

			$memcache_key = $this->conn->getDatabase() . "_extractor_cache:$cache_key:$this->lang";
			$this->debug("MEMCACHE:: using key $memcache_key extraction\n");
			if (!$this->avoid_cache) {
				$this->debug("CACHE:: avoid_cache desactivado\n");
				if (!$this->preview) {// si no estem fent preview, mirem si esta activada la memcache i si existeix la key
					$this->debug("CACHE:: preview desactivado\n");
					if ($this->setupCache()) {

						$this->debug("CACHE:: " . $this->type_of_cache . ":: insertamos el objeto $memcache_key \n");

						$this->debug("CACHE:: VALUE inserted:\n");
						$this->debug($result);
						$this->debug("\nCACHE:: END VALUE inserted:\n");
						if (isset($params['extraction_cache_expiration'])) {
							$this->setCache($memcache_key, $result, $params['extraction_cache_expiration']);
						} else {
							$this->setCache($memcache_key, $result);
						}
					}
				}
			}
		}
	}

	public function getPaginator($prefix = '', $postfix = '') {
//lastPage
//firstPage
//hasMorePages
//nextPage
//previousPage
//onFirstPage
//currentPage
// generate elements if not exists

		if ($this->paginator) {
			if (!isset($this->paginator['elements'])) {
				for ($i = $this->paginator['firstPage']; $i <= $this->paginator['lastPage']; $i++) {
					$element = array();
					$element['url'] = $prefix . $i . $postfix;
					$element['isFirst'] = $i == $this->paginator['firstPage'];
					$element['isLast'] = $i == $this->paginator['lastPage'];
					$element['isCurrent'] = $i == $this->paginator['currentPage'];
					$this->paginator['elements'][$i] = $element;
				}
			}
		}
		return $this->paginator;
	}

	private function getRelationDirection($inst_id, $relation) {
		$this->debug("Extractor::getRelationDirection inst_id=$inst_id relation=$relation\n");
		$class_id = $this->findClassIDFromInstID($inst_id);
		$sql = "select count(*) num
				from omp_relations r
				where ( r.child_class_id = $class_id OR FIND_IN_SET( $class_id, r.multiple_child_class_id ) )
				" . $this->getRelationFilter($relation) . "
				";

		$this->debug("Extractor::getRelationDirection sql=$sql\n");
		$num = $this->conn->fetchColumn($sql);
		if ($num > 0) {// La class es filla, retornem parent
			return 'parent';
		}


		$sql = "select count(*) num
				from omp_relations r
				where r.parent_class_id = $class_id
				" . $this->getRelationFilter($relation) . "
				";
		$this->debug("Extractor::getRelationDirection sql=$sql\n");
		$num = $this->conn->fetchColumn($sql);
		if ($num > 0) {// La class es pare, retornem child
			return 'child';
		}
		return null;
	}

	private function prepareInstanceResultStructure($row, $params = null, callable $callback = null, $start = null) {
		if (!$start)
			$start = microtime(true);
		if (!$row)
			return null;

		$instance = array();
		if (isset($row['inst_id'])) {
			$inst_id = $row['inst_id'];
		} else {
//print_r($row);
			$inst_id = $row['id'];
		}
		$instance['id'] = $inst_id;
		$instance['inst_id'] = $inst_id;
		$instance['link'] = $this->getInstanceLink($inst_id);

		if ($this->metadata) {
			$metadata = array();
			$metadata['id'] = $inst_id;
			$metadata['nom_intern'] = $row['nom_intern'];

			if (isset($row['external_id'])) {
				$metadata['external_id'] = $row['external_id'];
			} else {
				$metadata['external_id'] = null;
			}
			if (isset($row['batch_id'])) {
				$metadata['batch_id'] = $row['batch_id'];
			} else {
				$metadata['batch_id'] = null;
			}

			if (isset($row['publishing_begins']))
				$metadata['publishing_begins'] = $row['publishing_begins'];
			if (isset($row['publishing_ends']))
				$metadata['publishing_ends'] = $row['publishing_ends'];
			$metadata['class_id'] = $row['class_id'];
			$metadata['class_tag'] = $row['class_tag'];
			$metadata['class_name'] = $row['class_name'];
			$metadata['update_timestamp'] = $row['update_timestamp'];
			if (isset($row['relation_instance_weight']))
				$metadata['relation_instance_weight'] = $row['relation_instance_weight'];
			$instance['metadata'] = $metadata;
		}

		if ($this->extract_values) {
			$values = $this->getInstanceValues($inst_id, $row['update_timestamp'], $params);

			foreach ($values as $attrs_array_key => $full_value) {
				if (!is_array($full_value)) {
// TBD propagar les dades de cache a metadata
					$this->debug("Actualizamos metadata de la instancia $attrs_array_key amb valor $full_value\n");
					if ($this->metadata)
						$instance['metadata'][$attrs_array_key] = $full_value;
				} else {
//$this->debug("prepareInstanceResultStructure loop values array element:");
//$this->debug($full_value);
					if (isset($full_value['tag'])) {
						$key = $full_value['tag'];
						$value = null;
						if (isset($full_value['date_val']))
							$value = $full_value['date_val'];
						if (isset($full_value['num_val']))
							$value = $full_value['num_val'];
						if (isset($full_value['text_val']))
							$value = $full_value['text_val'];

						$instance[$key] = $value;
					}
				}
			}
		}
//echo "CALLBACK\n";
//var_dump($callback);die;

		if ($callback != null) {
			$this->debug("Voy a hacer el callback con inst_id=$inst_id\n");
			$relations = $callback($inst_id);
			if ($relations) {
				$instance['relations'] = $relations;
			}
		}

		if ($this->metadata && $this->timings) {
			$end = microtime(true);
			$instance['metadata']['start'] = $start;
			$instance['metadata']['end'] = $end;
			$instance['metadata']['microseconds'] = $end - $start;
		}

		return $instance;
	}

	function getInstanceValues($inst_id, $update_timestamp, $params) {
//// $inst_id 
// $lang = ALL | es | ca | en ...
// $filter = detail | resume | only-X | except-Y  | fields:fieldname1|fieldname2
// where 
// "detail" are values of attributes marked as detail='Y' in this particular class
// "resume"  are values of attributes marked as detail='N' in this particular class
// "only-X" are values only of the attribute_type=X
// "except-Y"  are values excluding attribute_type=Y
		$this->debug("Extractor::getInstanceValues id=$inst_id update_timestamp=$update_timestamp\n");

		$insert_in_cache = false;

		$filter = 'all';
		if (isset($params['filter']))
			$filter = $params['filter'];

		$memcache_key = $this->conn->getDatabase() . ":dbinterface:$this->lang:$inst_id:$filter";
		$this->debug("MEMCACHE:: using key $memcache_key instance update_timestamp=$update_timestamp\n");
		if (!$this->avoid_cache) {
			$this->debug("CACHE:: avoid_cache desactivado\n");
			if (!$this->preview) {// si no estem fent preview, mirem si esta activada la memcache i si existeix la key
				$this->debug("CACHE:: preview desactivado\n");
				if ($this->setupCache()) {
					$this->debug("CACHE:: setupCache OK\n");
					$memcache_value = $this->mc->get($memcache_key);
					if ($memcache_value) {// existe, retornamos directamente si la info esta actualizada
						$this->debug("CACHE:: existe el value\n");
						$this->debug($this->type_of_cache . ":: instance last updated at $update_timestamp !!!!\n");
						$this->debug($this->type_of_cache . ":: value for key $memcache_key\n");
//$this->debug(print_r($memcache_value, true));
						if (isset($memcache_value['cache_timestamp'])) {// tenim el timestamp a l'objecte
							if ($update_timestamp < $memcache_value['cache_timestamp']) {// l'objecte es fresc, el retornem
								$memcache_value['cache_timestamp'] = time();
								$memcache_value['cache_status'] = 'hit';
								$this->debug($this->type_of_cache . ":: HIT lo renovamos!!!\n");
								$this->setCache($memcache_key, $memcache_value);
								//$this->debug("CACHE:: antes de retornar el memcache_value\n");
								//$this->debug(print_r($memcache_value, true));
								return $memcache_value;
							} else {// no es fresc, l'esborrem i donem ordres de refrescar-lo												
								$this->debug($this->type_of_cache . ":: purgamos el objeto ya que $update_timestamp es mayor o igual a " . $memcache_value['cache_timestamp'] . "\n");
								$this->mc->delete($memcache_key);
								$insert_in_cache = true;
							}
						} else {// no te el format correcte, l'expirem
							$this->debug($this->type_of_cache . ":: purgamos el objeto ya que no tiene cache_timestamp\n");
							$this->mc->delete($memcache_key);
							$insert_in_cache = true;
						}
					} else {// no lo tenemos lo insertamos al final
						$insert_in_cache = true;
					}
				}
			}
		}



//echo $filter;die;
		$add_sql = '';
		if ($filter == 'detail') {
			$add_sql = "
						and ca.detail='Y'
						";
		}
		if ($filter == 'resume') {
			$add_sql = "
						and ca.detail='N'
						";
		}
		if (substr($filter, 0, 5) == 'only-') {
			$add_sql = "
						and a.type='" . substr($filter, 5) . "'
						";
		}
		if (substr($filter, 0, 7) == 'except-') {
			$add_sql = "
						and a.type!='" . substr($filter, 7) . "'
						";
		}

		if (substr($filter, 0, 7) == 'fields:') {
			$field_list_str = substr($filter, 7);
			$fields_arr = explode('|', $field_list_str);

			$add_sql = "
						and a.tag in ('" . implode("','", $fields_arr) . "')
						";
		}



		$sql = "select i.id inst_id, a.id atri_id, a.name atri_name, a.tag atri_tag, a.type atri_type, a.language atri_language, ca.detail is_detail, i.update_date, ifnull(unix_timestamp(i.update_date),0) update_timestamp 
				from omp_attributes a
				, omp_class_attributes ca
				, omp_instances i
				where i.id=$inst_id
				and a.language in ('ALL', '" . $this->lang . "')
				and i.class_id=ca.class_id
				and a.id=ca.atri_id
				$add_sql
				order by if(a.language='ALL',1,0), atri_id
				";
//$this->debug($sql);

		$tags_with_value = [];
		$images_ids=[];

		$attrs = $this->conn->fetchAll($sql);
		foreach ($attrs as $attr_key => $attr_val) {
			if (is_array($attr_val)) {
				$value_row_or_null_array = $this->getValueRowOrNullArray($attrs[$attr_key]['inst_id'], $attrs[$attr_key]['atri_id'], $attrs[$attr_key]['atri_type']);
//echo '!!! atri_id='.$attrs[$attr_key]['atri_id'].' amb tag '.$attrs[$attr_key]['atri_tag'].' amb valor '.$value_row_or_null_array['text_val']."\n";				
//				if (!isset($attrs[$attr_key]['tag']) || (isset($attrs[$attr_key]['tag'])) && $attrs[$attr_key]['tag']==null)
				if (!in_array($attrs[$attr_key]['atri_tag'], $tags_with_value)) {// caso en que no tenemos el tag del atributo previamente del idioma ALL o lo tenemos a null
					if ($value_row_or_null_array['id'] != null) {
						$tags_with_value[] = $attrs[$attr_key]['atri_tag'];
					}
//echo "Hasta ahora tenemos:\n";
//print_r($tags_with_value);
//echo "\n";

					$attrs[$attr_key]['id'] = $value_row_or_null_array['id'];
					$attrs[$attr_key]['text_val'] = $value_row_or_null_array['text_val'];
					$attrs[$attr_key]['num_val'] = $value_row_or_null_array['num_val'];
					$attrs[$attr_key]['date_val'] = $value_row_or_null_array['date_val'];
					$attrs[$attr_key]['img_info'] = $value_row_or_null_array['img_info'];
					$attrs[$attr_key]['tag'] = $attrs[$attr_key]['atri_tag'];

					foreach ($attr_val as $subkey => $subval) {// apliquem la transformació per canviar nls a brs
//echo "key=$attr_key subkey=$subkey val=$subval\n";
						if ($subkey == 'atri_type') {// casos especials depenent del atri_type
							if ($subval == 'A') {
								$attrs[$attr_key]['text_val'] = str_replace(array("\r\n", "\r", "\n"), "<br />", $attrs[$attr_key]['text_val']);
							}
							if ($subval == 'L') {
								$attrs[$attr_key]['text_val'] = $this->getLookupValue($attrs[$attr_key]['num_val']);
							}
							if ($subval == 'D') {
								$attrs[$attr_key]['text_val'] = $attrs[$attr_key]['date_val'];
							}
							if (($subval == 'F' || $subval == 'I') && substr($attrs[$attr_key]['text_val'], 0, 8) == 'uploads/') {// Backwards compatibility with editoras that save uploads/ instead of /uploads/
								$attrs[$attr_key]['text_val'] = '/' . $attrs[$attr_key]['text_val'];
							}
							if ($subval == 'I') {
								$tag=$attrs[$attr_key]['tag'];
								$attrs[$tag.'_imgid']['tag'] = $tag.'_imgid';
								$attrs[$tag.'_imgid']['text_val'] = $attrs[$attr_key]['id'];
							}
							
						}
					}
				}
			}

//echo "El array de resultado tiene:\n";
//print_r($attrs);
		}

		foreach ($attrs as $key => $attr) {// Eliminamos los elementos que no han conseguido tener tag
			if (!isset($attr['tag'])) {
				unset($attrs[$key]);
			}
		}

//echo "El array de resultado tiene:\n";
//print_r($attrs);


		if ($insert_in_cache) {
			$attrs['cache_timestamp'] = time();
			$attrs['cache_status'] = 'miss';
			$attrs['cache_filter'] = $filter;

			$this->debug($this->type_of_cache . ":: insertamos el objeto $memcache_key \n");
			$this->debug($attrs);
			$this->setCache($memcache_key, $attrs);
		}
		return $attrs;
	}

	function getLookupValue($id) {
		$sql = "select value
				from omp_lookups_values 
				where id=$id
				";
		$row = $this->conn->fetchAssoc($sql);
		if (!$row) {
			return null;
		}

		return $row['value'];
	}

	function getValueRowOrNullArray($inst_id, $atri_id, $type) {
		$sql = "select *
			from omp_values
			where inst_id=$inst_id
			and atri_id=$atri_id
			limit 1
			";
//$this->debug($sql);
		$values = $this->conn->fetchAssoc($sql);
		if (!$values) {
			$values['id'] = null;
			$values['text_val'] = null;
			$values['date_val'] = null;
			$values['num_val'] = null;
			$values['img_info'] = null;
			if ($type == 'L') {
				$sql = "SELECT lv.id 
				FROM omp_lookups_values lv
				, omp_lookups l
				, omp_attributes a
				where a.id=$atri_id
				and a.lookup_id=l.id
				and a.type='L'
				and l.id=lv.lookup_id
				order by lv.ordre
				limit 1
				";
//$this->debug($sql);
				$lookup_id = $this->conn->fetchColumn($sql);
				$values['id'] = -1;
				$values['num_val'] = $lookup_id;
			}
		}
		return $values;
	}

	private function setPagination($num, $class_filter, $preview_filter, $order_filter = "", $ids_filter = "", $search_filter = "") {
		if ($num != null && $this->paginator == null) {
			if (stripos($num, '/')) {
				$pagination_array = explode("/", $num);
				if (isset($pagination_array[0]) && isset($pagination_array[1]) && is_numeric($pagination_array[0]) && is_numeric($pagination_array[1]) && $pagination_array[1] > 0) {

					$limit = $pagination_array[0];
					$offset = ($pagination_array[1] - 1) * $limit;
					$sql = "select count(*) num 
          from omp_instances i 
          , omp_classes c
          where 1=1
          $class_filter
          and c.id=i.class_id
          $preview_filter
					$ids_filter
					$search_filter
          ";
					$total = $this->conn->fetchColumn($sql);
					$pagination_info['lastPage'] = (int) ceil($total / $pagination_array[0]);
					$pagination_info['firstPage'] = 1;
					if ($total > $limit + $offset) {
						$pagination_info['hasMorePages'] = true;
						$pagination_info['nextPage'] = (int) $pagination_array[1] + 1;
					} else {
						$pagination_info['hasMorePages'] = false;
						$pagination_info['previousPage'] = null;
					}
					if ($pagination_array[1] == 1) {
						$pagination_info['onFirstPage'] = true;
						$pagination_info['previousPage'] = null;
					} else {
						$pagination_info['onFirstPage'] = false;
						$pagination_info['previousPage'] = (int) $pagination_array[1] - 1;
					}
					$pagination_info['currentPage'] = $pagination_array[1];
				}
			}
			$this->paginator = $pagination_info;
		}
	}

}
