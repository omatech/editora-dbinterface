<?php
namespace Omatech\Editora;

class DBInterfaceBase {

	protected $conn;
	protected $params;
	protected $cache_expiration = 3600;
	protected $type_of_cache = null;
	protected $mc = null;
	protected $debug = false;
	protected $avoid_cache = false;
	public $debug_messages = '';
	protected $show_inmediate_debug = false;
	protected $timings = false;

	public function setParams($params)
	{
		$this->params=$params;
		foreach ($params as $key => $value) {
			//echo "Parsing $key=$value\n";
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
	}
	
	public function __construct($conn, $params=array()) {

		$this->setParams($params);
		if (is_array($conn)) {
			$config = new \Doctrine\DBAL\Configuration();
			if ($this->debug) {
				$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
			}			
			//print_r($conn);
			$conn = \Doctrine\DBAL\DriverManager::getConnection($conn, $config);
		}
		$this->conn = $conn;
	}
	
	function getAttrInfo($key) {
		if (is_numeric($key)) {
			$key = $this->conn->quote($key);
			$sql = "SELECT * FROM omp_attributes where id=$key";
		} else {
			$key = $this->conn->quote($key);
			$sql = "SELECT * FROM omp_attributes where name=$key";
		}
		return $this->conn->fetchAssoc($sql);
	}		

	public function findClassIDFromInstID($inst_id) {
		$this->debug("Extractor::findClassIDFromInstID inst_id=$inst_id\n");
		$sql = "select class_id from omp_instances where id=$inst_id";
		return $this->conn->fetchColumn($sql);
	}

	public function findClass($class) {
		$this->debug("Extractor::findClass class=$class\n");

		$sql = "select c.id class_id, c.name, c.tag
				from omp_classes c
				where 1=1
				" . $this->getClassFilter($class) . "
				limit 1
				";

		$this->debug($sql);
		$row = $this->conn->fetchAssoc($sql);
		if (!$row)
			return null;
		return $row;
	}

	public function findRelation($relation, $inst_id) {
		$this->debug("Extractor::findRelation relation=$relation inst_id=$inst_id\n");

		$class_id=$this->findClassIDFromInstID($inst_id);
		$sql = "select r.id, r.tag, r.parent_class_id, r.child_class_id, r.multiple_child_class_id
				from omp_relations r
				where 1=1
				" . $this->getRelationFilter($relation) . "
				and ( r.child_class_id = $class_id OR FIND_IN_SET( $class_id, r.multiple_child_class_id ) OR r.parent_class_id = $class_id)
				limit 1
				";

		$this->debug($sql);
		$row = $this->conn->fetchAssoc($sql);
		if (!$row)
			return null;
		return $row;
	}

	public function findRelationID($relation, $inst_id) {
		return $this->findRelation($relation, $inst_id)['id'];
	}

	public function getInstanceLink($inst_id) {
		$sql = "select niceurl
				from omp_niceurl
				where inst_id=$inst_id
				and language='" . $this->lang . "'";
		//$this->debug("SQL a getLink\n");
		//$this->debug($sql);
		$niceurl_row = $this->conn->fetchAssoc($sql);
		if ($niceurl_row) {
			if ($this->lang == 'ALL') {
				$link = '/' . $niceurl_row['niceurl'];
			} else {
				$link = '/' . $this->lang . '/' . $niceurl_row['niceurl'];
			}
		} else {
			if ($this->lang == 'ALL') {
				$link = '/' . $inst_id;
			}
			else
			{
				$link = '/' . $this->lang . '/' .$inst_id;
			}
		}

		return $link;
	}

	function setCache($memcache_key, $memcache_value) {
		if ($this->type_of_cache == 'memcached') {
			$this->mc->set($memcache_key, $memcache_value, $this->cache_expiration);
		} else {// memcache standard
			$this->mc->set($memcache_key, $memcache_value, MEMCACHE_COMPRESSED, $this->cache_expiration);
		}
	}

	function setupCache() {// set up the type_of_cache (memcache or memcached) and a handler or false if cache is not available
		if ($this->mc != null && $this->type_of_cache != null)
			return true;

		$memcacheAvailable = false;
		if (extension_loaded('Memcached')) {
			$type_of_cache = 'memcached';
			try {
				$mc = new \Memcached;
				$mc->setOption(\Memcached::OPT_COMPRESSION, true);
				$memcacheAvailable = $mc->addServer('localhost', 11211);
			} catch (Exception $e) {
				return false;
			}
		} elseif (extension_loaded('Memcache')) {
			$type_of_cache = 'memcache';
			try {
				$mc = new \Memcache;
				$memcacheAvailable = $mc->connect('localhost', 11211);
			} catch (Exception $e) {
				return false;
			}
		} else {
			return false;
		}

		if ($memcacheAvailable) {
			$this->mc = $mc;
			$this->type_of_cache = $type_of_cache;
			return true;
		} else {
			return false;
		}
	}
	
	
	function clean_url($url, $id = '') {
		if ('' == $url)
			return $url;
		$url = trim($url);
		$url = strip_tags($url);

		$search = array(
			"à", "á", "â", "ã", "ä", "À", "Á", "Â", "Ã", "Ä",
			"è", "é", "ê", "ë", "È", "É", "Ê", "Ë",
			"ì", "í", "î", "ï", "Ì", "Í", "Î", "Ï",
			"ó", "ò", "ô", "õ", "ö", "Ó", "Ò", "Ô", "Õ", "Ö",
			"ú", "ù", "û", "ü", "Ú", "Ù", "Û", "Ü",
			",", ".", ";", ":", "`", "´", "<", ">", "?", "}",
			"{", "ç", "Ç", "~", "^", "Ñ", "ñ"
		);
		$change = array(
			"a", "a", "a", "a", "a", "A", "A", "A", "A", "A",
			"e", "e", "e", "e", "E", "E", "E", "E",
			"i", "i", "i", "i", "I", "I", "I", "I",
			"o", "o", "o", "o", "o", "O", "O", "O", "O", "O",
			"u", "u", "u", "u", "U", "U", "U", "U",
			" ", "-", " ", " ", " ", " ", " ", " ", " ", " ",
			" ", "c", "C", " ", " ", "NY", "ny"
		);

		$url = strtoupper(str_ireplace($search, $change, $url));
		$temp = explode("/", $url);
		$url = $temp[count($temp) - 1];

		$url = preg_replace('|[^a-z0-9-~+_. #=&;,/:]|i', '', $url);
		$url = str_replace('/', '', $url);
		$url = str_replace(' ', '-', $url);
		$url = str_replace('&', '', $url);
		$url = str_replace("'", "", $url);
		$url = str_replace(';//', '://', $url);
		$url = preg_replace('/&([^#])(?![a-z]{2,8};)/', '&#038;$1', $url);

		$url = strtolower($url);

		//ultims canvis
		$url = trim(str_replace("[^ A-Za-z0-9_-]", "", $url));
		$url = str_replace("[ \t\n\r]+", "-", $url);
		$url = str_replace("[ -]+", "-", $url);

		if ($id == '')
			return $url;

		return $url . "-" . $id;
	}

		public function relationInstanceExist($rel_id, $parent_inst_id, $child_inst_id) {
		$sql = "select id 
				from omp_relation_instances 
				where rel_id=$rel_id 
				and parent_inst_id=$parent_inst_id 
				and child_inst_id=$child_inst_id;";
		$row = $this->conn->fetchAssoc($sql);
		if ($row) {
			return $row['id'];
		}
		return false;
	}
	
	public function getInstIDFromNomIntern($class_tag, $nom_intern) {// retorna -1 si no existeix la instancia d'aquesta class amb el nom intern indicat
		$class_tag = $this->conn->quote($class_tag);
		$nom_intern = $this->conn->quote($nom_intern);

		$sql = "SELECT i.id
				FROM omp_instances i
				, omp_classes c
				WHERE 
				 i.class_id = c.id
				AND c.tag=$class_tag
				AND i.key_fields=$nom_intern
				";

		$row = $this->conn->fetchAssoc($sql);

		if ($row) {
			return $row['id'];
		}
		return -1;
	}	
	
	public function getInstIDFromValue($class_tag, $atri, $value) {// retorna -1 si no existeix la instancia d'aquesta class o el id si existeix
		$class_tag = $this->conn->quote($class_tag);
		$value = $this->conn->quote($value);

		$atri_info = $this->get_attr_info($atri);
		$atri_id = $atri_info['id'];

		$sql = "SELECT i.id
				FROM omp_instances i
				, omp_classes c
				, omp_values v
				WHERE 
				 i.class_id = c.id
				AND c.tag=$class_tag
				AND v.inst_id = i.id
				AND v.atri_id = $atri_id
				AND v.text_val = $value
				";

		$row = $this->conn->fetchAssoc($sql);

		if ($row) {
			return $row['id'];
		}
		return -1;
	}

	public function getInstIDFromNumericValue($class_tag, $atri, $value) {// retorna -1 si no existeix la instancia d'aquesta class o el id si existeix
		$class_tag = $this->conn->quote($class_tag);
		//$value = $this->conn->quote($value);

		$atri_info = $this->get_attr_info($atri);
		$atri_id = $atri_info['id'];

		$sql = "SELECT i.id
				FROM omp_instances i
				, omp_classes c
				, omp_values v
				WHERE 
				 i.class_id = c.id
				AND c.tag=$class_tag
				AND v.inst_id = i.id
				AND v.atri_id = $atri_id
				AND v.num_val = $value
				";

		$row = $this->conn->fetchAssoc($sql);

		if ($row) {
			return $row['id'];
		}
		return -1;
	}
	
	
	public function getInstIDFromNiceURL($nice_url, $language) {
		return $this->getInstIDFromURLNice($nice_url, $language);
	}
		public function getInstIDFromURLNice($nice_url, $language) {
		$sql = "select inst_id from omp_niceurl where niceurl='$nice_url' and language='$language'";
		$inst_id = $this->conn->fetchColumn($sql);
		return $inst_id;
	}
	
	public function getInstanceRowAndExistingValues($inst_id) {
		$sql = "select * 
				from omp_instances
				where id=$inst_id";
		$current_inst = $this->conn->fetchAssoc($sql);

		$sql = "select a.name, a.type, v.text_val, v.num_val, v.date_val 
				from omp_values v
				, omp_attributes a
				where a.id=v.atri_id
				and v.inst_id=$inst_id";

		$rows = $this->conn->fetchAll($sql);

		$current_inst['values'] = $rows;
		return $current_inst;
	}

	public function existInstance($inst_id) {
		$sql = "select count(*) num 
				from omp_instances
				where id=$inst_id
				";
		$row = $this->conn->fetchAssoc($sql);
		return ($row['num'] == 1);
	}	
	
	public function existsInstanceWithExternalID($class_id, $external_id) {// return false if not exists, inst_id if exists
		$external_id = $this->conn->quote($external_id);
		$sql = "select id from omp_instances where external_id=$external_id and class_id=$class_id limit 1";
		$inst_id = $this->conn->fetchColumn($sql);
		return $inst_id;
	}	
	
	public function existsNiceURL($nice_url, $language) { 
		return $this->existsURLNice($nice_url, $language);  
	}
	
	public function existsURLNice($nice_url, $language) {
		$sql = "select count(*) num from omp_niceurl where niceurl='$nice_url' and language='$language'";
		$num = $this->conn->fetchColumn($sql);
		return $num > 0;
	}
	
	
	function getLookupValueID($lookup_id, $value) {
		$value = $this->conn->quote($value);

		$sql = "select lv.id
				from omp_lookups_values lv
				where lv.lookup_id=$lookup_id
				and (lv.value=$value or
						lv.caption_ca=$value or
						lv.caption_es=$value or 
						lv.caption_en=$value
				)
				";

		return $this->conn->fetchAssoc($sql);
	}

	public function existValue($inst_id, $atri_id) {
		$sql = "select count(*) num
				from omp_values v
				where v.inst_id=$inst_id
				and v.atri_id=$atri_id
				";
		$row = $this->conn->fetchAssoc($sql);
		return ($row['num'] == 1);
	}	
	
	
	protected function debug($str) {
		$add = '';
		if ($this->debug) {
			if (is_array($str)) {
				$add .= print_r($str, true);
			} else {// cas normal, es un string
				$add .= $str;
			}

			$this->debug_messages .= $add;
			if ($this->show_inmediate_debug)
				echo $add;
		}
	}	
	
	
	protected function getSearchFilter($query) {
		return " and MATCH (s.text) AGAINST ('" . $this->conn->quote($query) . "' in boolean mode) 
			and (s.language = '" . $this->lang . "' OR s.language = 'ALL')
		";
	}

	public function updateInstanceOrderString ($inst_id, $value)
	{
		$value=$this->conn->quote($value);
		$sql="update omp_instances set order_string=$value where id=$inst_id";
		return $this->conn->executeQuery($sql);
	}
	
	public function updateInstanceOrderDate ($inst_id, $value)
	{
		$value=$this->conn->quote($value);
		$sql="update omp_instances set order_date=$value where id=$inst_id";
		return $this->conn->executeQuery($sql);
	}	
	
	protected function getOrderFilter($order, $order_direction = null, $previous_order_filter = null) {
		if (isset($order)) {
			if (strtolower($order) == 'update_date') {
				$order_sql = " order by i.update_date ";
			}
			if (strtolower($order) == 'inst_id') {
				$order_sql = " order by i.id ";
			}
			if (strtolower($order) == 'key_fields') {
				$order_sql = " order by i.key_fields ";
			}
			if (strtolower($order) == 'publishing_begins') {
				$order_sql = " order by i.publishing_begins ";
			}
			if (strtolower($order) == 'order_string') {
				$order_sql = " order by i.order_string ";
			}
			if (strtolower($order) == 'order_date') {
				$order_sql = " order by i.order_date ";
			}
		} else {
			if ($previous_order_filter != null) {
				$order_sql = $previous_order_filter;
			} else {
				$order_sql = " order by i.publishing_begins ";
			}
		}

		if (isset($order_direction) && strtolower($order_direction) == 'desc') {
			$order_sql .= " desc";
		}

		return $order_sql;
	}

	protected function getLimitFilter($num = null) {
		// $num can be only integer or with syntax 10/3 (give 10 elements in page 3 (elements from 31 to 40)
		$default=100000000;
		if ($num != null) {
			if (stripos($num, '/'))
			{
				$pagination_array=explode("/", $num);
				if (isset($pagination_array[0]) && isset($pagination_array[1]) && is_numeric($pagination_array[0]) && is_numeric($pagination_array[1]) && $pagination_array[1]>0)
				{
					$limit=$pagination_array[0];
					$offset=($pagination_array[1]-1)*$limit;
					return " limit $limit offset $offset ";
				}
				else
				{
					$this->debug("Limit syntax incorrect $num for example 10/1 gives first 10 records 10/2 gives from 11 to 20 etc.");
					return " limit $default ";
				}
			}
			else
			{
				
			}
			return " limit $num ";
		} else {
			return " limit $default ";
		}
	}

	protected function getPreviewFilter() {
		$filter = '';

		if (!$this->preview) {
			$filter .= "
					and i.status = 'O'
					";
			$filter .= "
				  and DATE_FORMAT(i.publishing_begins,'%Y%m%d%H%i%S') <= NOW()+0
				  and IFNULL(DATE_FORMAT(i.publishing_ends,'%Y%m%d%H%i%S'),now()+1) > NOW()+0";
		} else {

			$filter .= "
				  and DATE_FORMAT(i.publishing_begins,'%Y%m%d%H%i%S') <= " . $this->preview_date . "+0
				  and IFNULL(DATE_FORMAT(i.publishing_ends,'%Y%m%d%H%i%S'),now()+1) > " . $this->preview_date . "+0";
		}
		return $filter;
	}

	protected function getClassFilter($class) {
		if (is_numeric($class)) {

			return " and c.id=$class ";
		} else {
			return " and c.tag='$class' ";
		}
	}

	protected function getRelationFilter($relation) {
		if (is_numeric($relation)) {

			return " and r.id=$relation ";
		} else {
			return " and r.tag='$relation' ";
		}
	}

	protected function getIDsListFilter($inst_ids) {
		return " and i.id in (" . $inst_ids . ") ";
	}

    public function startTransaction()
    {
        $this->conn->executeQuery('start transaction');
    }
    public function commit()
    {
        $this->conn->executeQuery('commit');
    }

    public function rollback()
    {
        $this->conn->executeQuery('rollback');
    }
	
}
