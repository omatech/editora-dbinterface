<?php

namespace Omatech\Editora\Loader;

use \Omatech\Editora\DBInterfaceBase;
use \Doctrine\DBAL\DriverManager;

class Loader extends DBInterfaceBase
{
    public $file_base = '';
    public $url_base = '';
    public $geocoder;
    public $download_images = true;

    public function __construct($conn, $params = array(), $geocoder = null)
    {
        parent::__construct($conn, $params);
        $this->geocoder = $geocoder;
    }

    public function bulkImportInstances($rows)
    {
        $this->bulkImportTable('omp_instances', $rows);
    }

    public function bulkImportRelationInstances($rows)
    {
        $this->bulkImportTable('omp_relation_instances', $rows);
    }

    public function bulkImportStaticTexts($rows)
    {
        $this->bulkImportTable('omp_static_text', $rows);
    }

    public function bulkImportValues($rows)
    {
        $this->bulkImportTable('omp_values', $rows);
    }

    public function bulkImportTable($table_name, $rows, $rows_to_process = 1000, $delete_first = false)
    {
        if ($delete_first) {
            $this->conn->executeQuery("delete from $table_name");
        }

        $fields_array = array();
        if (!$rows) {
            return;
        }
        foreach ($rows[0] as $key => $val) {
            $fields_array[] = $key;
        }
        $fields = implode(',', $fields_array);

        $i = 1;
        $initial_sql = "insert into $table_name ($fields)	values ";
        $sql = $initial_sql;
        foreach ($rows as $row) {
            $sql .= '(';
            foreach ($row as $val) {
                if (isset($val) && !is_numeric($val)) {
                    $val=$this->conn->quote($val);
                }
                if (!$val) {
                    $val='null';
                }
                $sql .= "$val,";
            }
            $sql = substr($sql, 0, -1);
            $sql .= "),";


            if ($i % $rows_to_process == 0) {// ja portem 1000, executem
                $sql = substr($sql, 0, -1); // eliminem la ultima ,
                echo $i;
                //echo PHP_EOL . $i . ' - ' . $sql . PHP_EOL;
                if (method_exists($this->conn, 'query')) {
                    $this->conn->query($sql);
                } else {
                    $this->conn->executeQuery($sql);
                }
                $sql = $initial_sql;
            } else {
                echo '.';
            }
            $i++;
        }
        $sql = substr($sql, 0, -1); // eliminem la ultima ,
        echo "$i\n";
        //echo "$sql\n";
        //$this->last_massive_sql = $sql;
        if (method_exists($this->conn, 'query')) {
            $this->conn->query($sql);
        } else {
            $this->conn->executeQuery($sql);
        }
        return $i;
    }

    public function delete_relation_instances_in_batch($batch_id)
    {
        $sql = "delete from omp_relation_instances where batch_id=:batch_id";
        $statement = $this->conn->prepare($sql);
        $statement->bindValue('batch_id', $batch_id);
        $statement->execute();
        echo $statement->rowCount() . " relation instances deleted\n";
    }

    public function delete_instances_in_batch($batch_id)
    {
        //$batch_id = $this->conn->quote($batch_id);
        $sql = "select id from omp_instances where batch_id=$batch_id";
        $rows = $this->fetchAll($sql);

        if ($rows) {
            foreach ($rows as $row) {
                $inst_id = $row['id'];
                echo "Deleting instance $inst_id\n";
                $this->delete_instance($inst_id);
            }
        } else {
            echo "Nothing to delete for batch_id=$batch_id\n";
        }
    }
    
    public function deleteInstancesWithExternalID($external_id, $class_id)
    {
        $sql = "select id from omp_instances where external_id=$external_id and class_id=$class_id";
        $rows = $this->fetchAll($sql);
        
        if ($rows) {
            foreach ($rows as $row) {
                $inst_id = $row['id'];
                echo "Deleting instance $inst_id\n";
                $this->delete_instance($inst_id);
            }
        } else {
            echo "Nothing to delete for external_id=$external_id\n";
        }
    }

    public function deleteRelationInstancesWithParameters($rel_id, $parent_inst_id, $child_inst_id, $external_id)
    {
        $sql = "delete from omp_relation_instances where rel_id=:rel_id and parent_inst_id=:parent_inst_id and child_inst_id=:child_inst_id and external_id=:external_id";
        $statement = $this->conn->prepare($sql);
        $statement->bindValue('rel_id', $rel_id);
        $statement->bindValue('parent_inst_id', $parent_inst_id);
        $statement->bindValue('child_inst_id', $child_inst_id);
        $statement->bindValue('external_id', $external_id);
        $statement->execute();
        echo $statement->rowCount() . " relation instances deleted\n";
    }

    public function deleteRelationInstancesWithExternalID($external_id, $rel_id)
    {
        $sql = "delete from omp_relation_instances where external_id=:external_id and rel_id=:rel_id";
        $statement = $this->conn->prepare($sql);
        $statement->bindValue('external_id', $external_id);
        $statement->bindValue('rel_id', $rel_id);
        $statement->execute();
        echo $statement->rowCount() . " relation instances deleted\n";
    }
    
    public function delete_instance($inst_id)
    {
        return $this->deleteInstance($inst_id);
    }

    public function deleteInstance($inst_id)
    {
        $sql_values = 'DELETE
				FROM omp_values 
				WHERE inst_id = "' . $inst_id . '"';
        $this->conn->executeQuery($sql_values);
        //$ret_values = mysql_query($sql_values);

        $sql_inst_child = 'DELETE
				FROM omp_relation_instances 
				WHERE child_inst_id = "' . $inst_id . '"';
        $this->conn->executeQuery($sql_inst_child);
        //$ret_inst_child = mysql_query($sql_inst_child);

        $sql_inst_parent = 'DELETE
				FROM omp_relation_instances 
				WHERE parent_inst_id = "' . $inst_id . '"';
        $this->conn->executeQuery($sql_inst_parent);
        //$ret_inst_parent = mysql_query($sql_inst_parent);

        $sql_inst = 'DELETE 
				FROM omp_instances 
				WHERE id = "' . $inst_id . '"';
        $this->conn->executeQuery($sql_inst);
        //$ret_inst = mysql_query($sql_inst);

        $sql_inst = 'DELETE 
				FROM omp_niceurl 
				WHERE inst_id = "' . $inst_id . '"';
        $this->conn->executeQuery($sql_inst);
        //$ret_inst = mysql_query($sql_inst);

        $sql_inst = 'DELETE 
				FROM omp_instances_cache 
				WHERE inst_id = "' . $inst_id . '"';
        $this->conn->executeQuery($sql_inst);
        //$ret_inst = mysql_query($sql_inst);

        return true;
    }

    public function getAllInstancesClassId($class_id, $batch_id = null)
    {
        $sql = 'select id
				from omp_instances
				where class_id = "' . $class_id . '"';

        if ($batch_id != null) {
            $sql .= ' AND batch_id = "' . $batch_id . '";';
        } else {
            $sql .= ';';
        }

        $row = $this->fetchAll($sql);
        if (!$row) {
            return null;
        }
        return $row;


        return $this->conn->executeQuery($sql);
    }

    public function insertRelationInstance($rel_id, $parent_inst_id, $child_inst_id, $external_id = null, $batch_id = null)
    {
        $rel_instance_id = $this->relationInstanceExist($rel_id, $parent_inst_id, $child_inst_id);
        if ($rel_instance_id) {
            return $rel_instance_id;
        } else {// no existeix, la creem
            // calculem el seguent pes per aquest pare
            $sql = "SELECT min(ri.weight)-10 weight
						FROM omp_relation_instances ri
						WHERE ri.parent_inst_id = $parent_inst_id
						and ri.rel_id=$rel_id
						GROUP BY ri.rel_id, ri.parent_inst_id";

            $weight_row = $this->fetchAssoc($sql);

            if (empty($weight_row) || $weight_row["weight"] == -10) {
                $weight = 100000;
            } else {
                $weight = $weight_row["weight"];
            }

            $sql_fields_add = '';
            $sql_values_add = '';
            if ($external_id != null) {
                $sql_fields_add .= ', external_id';
                $sql_values_add .= ", $external_id";
            }
            if ($batch_id != null) {
                $sql_fields_add .= ', batch_id';
                $sql_values_add .= ", $batch_id";
            }


            $sql = "insert into omp_relation_instances 
						(rel_id, parent_inst_id , child_inst_id, weight, relation_date $sql_fields_add)
						values
						($rel_id, $parent_inst_id, $child_inst_id, $weight, NOW() $sql_values_add)";
            $ret = $this->conn->executeQuery($sql);
            return $this->conn->lastInsertId();
        }
    }

    public function ExistingInstanceIsDifferent($inst_id, $nom_intern, $values, $status = 'O', &$difference=null, &$attr_difference=null)
    {
        // -1 instance not exist
        // -2 status is different
        // -3 nom_intern is different
        // -4 some value is different
        // -5 some value not exists in current instance
        // 0 same!
        if (!$this->existInstance($inst_id)) {
            $difference = -1;
            return true;
        }

        $current_inst = $this->getInstanceRowAndExistingValues($inst_id);
        if ($status != $current_inst['status']) {
            $difference = -2;
            return true;
        }

        if ($nom_intern != $current_inst['key_fields']) {
            $difference = -3;
            return true;
        }

        $existing_attributes = array();
        foreach ($current_inst['values'] as $row) {
            $existing_attributes[] = $row['name'];
            if (array_key_exists($row['name'], $values)) {
                if (!empty($row['text_val']) && $values[$row['name']] != $row['text_val']) {
                    $difference = -4;
                    $attr_difference = $row['name'];
                    return true;
                }
                if (!empty($row['num_val']) && $values[$row['name']] != $row['num_val']) {
                    $difference = -4;
                    $attr_difference = $row['name'];
                    return true;
                }
                if (!empty($row['date_val']) && $values[$row['name']] != $row['date_val']) {
                    $difference = -4;
                    $attr_difference = $row['name'];
                    return true;
                }
            }
        }

        foreach ($values as $key => $val) {
            if (!in_array($key, $existing_attributes)) {
                $difference = -5;
                $attr_difference = $key;
                return true;
            }
        }

        $difference = 0;
        return false;
    }

    public function updateInstance($inst_id, $nom_intern, $values, $status = 'O', $publishing_begins = null, $publishing_ends = null)
    {
        if (!$this->existInstance($inst_id)) {
            return false;
        }

        $status = $this->conn->quote($status);

        if ($publishing_begins == null) {
            $publishing_begins = 'now()';
        } else {
            if (is_int($publishing_begins)) {// es un timestamp
                $publishing_begins = $this->conn->quote(date("Y-m-d H:m:s", $publishing_begins));
            } else {// confiem que esta en el format correcte
                $publishing_begins = $this->conn->quote($publishing_begins);
            }
        }

        if ($publishing_ends == null) {
            $publishing_ends = 'null';
        } else {
            if (is_int($publishing_ends)) {// es un timestamp
                $publishing_ends = $this->conn->quote(date("Y-m-d H:m:s", $publishing_ends));
            } else {// confiem que esta en el format correcte
                $publishing_ends = $this->conn->quote($publishing_ends);
            }
        }

        $sql = "update omp_instances
				set key_fields=" . $this->conn->quote($nom_intern) . "
				, status=$status
				, publishing_begins=$publishing_begins
				, publishing_ends=$publishing_ends
				, update_date=now()
				where id=$inst_id
				";
        $this->conn->executeQuery($sql);

        //!
        $ret = $this->updateValues($inst_id, array('nom_intern' => $nom_intern));
        if (!$ret) {
            //$this->conn->executeQuery('rollback');
            return false;
        }

        $ret = $this->updateValues($inst_id, $values);
        if (!$ret) {
            //$this->conn->executeQuery('rollback');
            return false;
        }

        $sql = "update omp_instances set update_date=now() where id=$inst_id";
        $this->conn->executeQuery($sql);

        return $inst_id;
    }

    public function updateUrlNice($nice_url, $inst_id, $language)
    {
        if (!$this->getHasUrlniceLanguage($inst_id, $language)) {
            return -1;
        }

        $sql = "update omp_niceurl set niceurl='$nice_url' where inst_id=$inst_id and language='$language'";
        $this->conn->executeQuery($sql);

        return $inst_id;
    }

    public function insertUrlNice($nice_url, $inst_id, $language)
    {
        if ($this->existsURLNice($nice_url, $language)) {
            return -1;
        }

        $sql = "insert into omp_niceurl 
						(inst_id, language , niceurl)
						values
						($inst_id, '$language','$nice_url')";
        $ret = $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }

    public function deleteInstancesInBatch($batch_id)
    {
        //$batch_id = $this->conn->quote($batch_id);
        $sql = "select id from omp_instances where batch_id=$batch_id";
        $rows = $this->fetchAll($sql);

        if ($rows) {
            foreach ($rows as $row) {
                $inst_id = $row['id'];
                echo "Deleting instance $inst_id\n";
                $this->deleteInstance($inst_id);
            }
        } else {
            echo "Nothing to delete for batch_id=$batch_id\n";
        }
    }

    public function deleteRelationInstancesInBatch($batch_id)
    {
        //$batch_id = $this->conn->quote($batch_id);
        $sql = "select id from omp_relation_instances where batch_id=$batch_id";
        $rows = $this->fetchAll($sql);

        if ($rows) {
            foreach ($rows as $row) {
                $rel_inst_id = $row['id'];
                echo "Deleting relation instance $rel_inst_id\n";
                $this->deleteRelationInstance($rel_inst_id);
            }
        } else {
            echo "Nothing to delete for batch_id=$batch_id\n";
        }
    }

    public function deleteRelationInstance($id)
    {
        $sql = "delete from omp_relation_instances where id=$id";
        $this->conn->executeQuery($sql);
    }

    public function insertInstanceWithExternalID($class_id, $nom_intern, $external_id, $batch_id, $values, $status = 'O', $publishing_begins = null, $publishing_ends = null, $creation_date = 'now()', $update_date = 'now()')
    {
        $status = $this->conn->quote($status);

        if ($publishing_begins == null) {
            $publishing_begins = 'now()';
        } else {
            if (is_int($publishing_begins)) {// es un timestamp
                $publishing_begins = $this->conn->quote(date("Y-m-d H:m:s", $publishing_begins));
            } else {// confiem que esta en el format correcte
                $publishing_begins = $this->conn->quote($publishing_begins);
            }
        }

        if ($publishing_ends == null) {
            $publishing_ends = 'null';
        } else {
            if (is_int($publishing_ends)) {// es un timestamp
                $publishing_ends = $this->conn->quote(date("Y-m-d H:m:s", $publishing_ends));
            } else {// confiem que esta en el format correcte
                $publishing_ends = $this->conn->quote($publishing_ends);
            }
        }

        if ($external_id) { 
            $external_id = $this->conn->quote($external_id);
        } else {
            $external_id = 'null';
        }

        if ($batch_id) { 
            $batch_id = $this->conn->quote($batch_id);
        } else {
            $batch_id = 'null';
        }

        $sql = "insert into omp_instances (class_id, key_fields, status, publishing_begins, publishing_ends, creation_date, update_date, external_id, batch_id)
						values ($class_id, " . $this->conn->quote($nom_intern) . ", $status, $publishing_begins, $publishing_ends, $creation_date, $update_date, $external_id, $batch_id)";
        $this->conn->executeQuery($sql);
        $inst_id = $this->conn->lastInsertId();

        $ret = $this->updateValues($inst_id, array('nom_intern' => $nom_intern));
        if (!$ret) {
            //$this->conn->executeQuery('rollback');
            return false;
        }

        $ret = $this->updateValues($inst_id, $values);
        if (!$ret) {
            //$this->conn->executeQuery('rollback');
            return false;
        }


        $sql = "update omp_instances set update_date=$update_date where id=$inst_id";
        $this->conn->executeQuery($sql);

        return $inst_id;
    }

    public function quote($str)
    {
        return $this->conn->quote($str);
    }

    public function insertInstance($class_id, $nom_intern, $values, $status = 'O', $publishing_begins = null, $publishing_ends = null)
    {
        $status = $this->conn->quote($status);

        if ($publishing_begins == null) {
            $publishing_begins = 'now()';
        } else {
            if (is_int($publishing_begins)) {// es un timestamp
                $publishing_begins = $this->conn->quote(date("Y-m-d H:m:s", $publishing_begins));
            } else {// confiem que esta en el format correcte
                $publishing_begins = $this->conn->quote($publishing_begins);
            }
        }

        if ($publishing_ends == null) {
            $publishing_ends = 'null';
        } else {
            if (is_int($publishing_ends)) {// es un timestamp
                $publishing_ends = $this->conn->quote(date("Y-m-d H:m:s", $publishing_ends));
            } else {// confiem que esta en el format correcte
                $publishing_ends = $this->conn->quote($publishing_ends);
            }
        }

        $sql = "insert into omp_instances (class_id, key_fields, status, publishing_begins, publishing_ends, creation_date, update_date)
						values ($class_id, " . $this->conn->quote($nom_intern) . ", $status, $publishing_begins, $publishing_ends, now(), now())";
        $this->conn->executeQuery($sql);
        $inst_id = $this->conn->lastInsertId();

        $ret = $this->updateValues($inst_id, ['nom_intern' => $nom_intern]);
        if (!$ret) {
            //$this->conn->executeQuery('rollback');
            return false;
        }

        $ret = $this->updateValues($inst_id, $values);
        if (!$ret) {
            //$this->conn->executeQuery('rollback');
            return false;
        }


        $sql = "update omp_instances set update_date=now() where id=$inst_id";
        $this->conn->executeQuery($sql);

        return $inst_id;
    }

    public function insertInstanceForcingID($inst_id, $class_id, $nom_intern, $values, $status = 'O', $publishing_begins = null, $publishing_ends = null)
    {
        $status = $this->conn->quote($status);
        $sql = "delete from omp_instances where id=$inst_id";
        $this->conn->executeQuery($sql);

        if ($publishing_begins == null) {
            $publishing_begins = 'now()';
        } else {
            if (is_int($publishing_begins)) {// es un timestamp
                $publishing_begins = $this->conn->quote(date("Y-m-d H:m:s", $publishing_begins));
            } else {// confiem que esta en el format correcte
                $publishing_begins = $this->conn->quote($publishing_begins);
            }
        }

        if ($publishing_ends == null) {
            $publishing_ends = 'null';
        } else {
            if (is_int($publishing_ends)) {// es un timestamp
                $publishing_ends = $this->conn->quote(date("Y-m-d H:m:s", $publishing_ends));
            } else {// confiem que esta en el format correcte
                $publishing_ends = $this->conn->quote($publishing_ends);
            }
        }

        $sql = "insert into omp_instances (id, class_id, key_fields, status, publishing_begins, publishing_ends, creation_date, update_date)
						values ($inst_id, $class_id, " . $this->conn->quote($nom_intern) . ", $status, $publishing_begins, $publishing_ends, now(), now())";
        $this->conn->executeQuery($sql);
        $inst_id = $this->conn->lastInsertId();

        $ret = $this->updateValues($inst_id, ['nom_intern' => $nom_intern]);
        if (!$ret) {
            //$this->conn->executeQuery('rollback');
            return false;
        }

        $ret = $this->updateValues($inst_id, $values);
        if (!$ret) {
            //$this->conn->executeQuery('rollback');
            return false;
        }


        $sql = "update omp_instances set update_date=now() where id=$inst_id";
        $this->conn->executeQuery($sql);

        return $inst_id;
    }

    public function updateValues($inst_id, $values)
    {
        $results = array();
        foreach ($values as $key => $value) {
            $attr_info = $this->getAttrInfo($key);
            if (empty($attr_info)) {
                return false;
            } else {// podem continuar, existeix l'atribut
                //print_r($attr_info);
                if ($attr_info['type'] == 'I') {// image
                    if ($this->download_images) {
                        $this->insertUpdateImageVal($inst_id, $attr_info['id'], $value);
                    } else {
                        $this->insertUpdateRemoteImageVal($inst_id, $attr_info['id'], $value);
                    }
                } elseif ($attr_info['type'] == 'B') {// string order
                    $this->insertUpdateTextVal($inst_id, $attr_info['id'], $value);
                    $this->updateInstanceOrderString($inst_id, $value);
                } elseif ($attr_info['type'] == 'D') {// date
                    $this->insertUpdateDateVal($inst_id, $attr_info['id'], $value);
                } elseif ($attr_info['type'] == 'E') {// date order
                    $this->insertUpdateDateVal($inst_id, $attr_info['id'], $value);
                    $this->updateInstanceOrderDate($inst_id, $value);
                } elseif ($attr_info['type'] == 'N') {// number
                    $this->insertUpdateNumVal($inst_id, $attr_info['id'], $value);
                } elseif ($attr_info['type'] == 'L') {// lookup
                    $this->insertUpdateLookupVal($inst_id, $attr_info['id'], $attr_info['lookup_id'], $value);
                } elseif ($attr_info['type'] == 'M') {// Maps
                    $this->insertUpdateGeoposVal($inst_id, $attr_info['id'], $value);
                } elseif ($attr_info['type'] == 'J') {// Json
                    $this->insertUpdateJsonVal($inst_id, $attr_info['id'], $value);
                } else {
                    $this->insertUpdateTextVal($inst_id, $attr_info['id'], $value);
                }
            }
        }
        return true;
    }

    public function insertUpdateGeoposVal($inst_id, $atri_id, $value)
    {
        if (strpos($value, '@') == true && strpos($value, ':') == true) {
            $value = $this->conn->quote($value);
        } else {
            $geoinfo = $this->$geocoder->geocode($value);
            $value = $this->conn->quote($geoinfo['lat'] . ':' . $geoinfo['lng'] . '@' . $value);
        }

        //print_r($geoinfo);die;
        $value = $this->conn->quote($geoinfo['lat'] . ':' . $geoinfo['lng'] . '@' . $value);
        if ($this->existValue($inst_id, $atri_id)) {// update
            $sql = "update omp_values v
						set v.text_val=$value
						where v.inst_id=$inst_id
						and v.atri_id=$atri_id
						and v.text_val!=$value
						";
        } else {// insert
            $sql = "insert into omp_values (inst_id, atri_id, text_val)
						values ($inst_id, $atri_id, $value)";
        }
        $this->conn->executeQuery($sql);
    }

    public function insertUpdateTextVal($inst_id, $atri_id, $value)
    {
        $value = $this->conn->quote($value);
        if ($this->existValue($inst_id, $atri_id)) {// update
            $sql = "update omp_values v
						set v.text_val=$value
						where v.inst_id=$inst_id
						and v.atri_id=$atri_id					  
						and v.text_val!=$value
						";
        } else {// insert
            $sql = "insert into omp_values (inst_id, atri_id, text_val)
						values ($inst_id, $atri_id, $value)";
        }
        $this->conn->executeQuery($sql);
    }

    public function insertUpdateJsonVal($inst_id, $atri_id, $value)
    {
        $value = $this->conn->quote($value);
        if ($this->existValue($inst_id, $atri_id)) {// update
            $sql = "update omp_values v
						set v.json_val=$value
						where v.inst_id=$inst_id
						and v.atri_id=$atri_id
						";
        } else {// insert
            $sql = "insert into omp_values (inst_id, atri_id, json_val)
						values ($inst_id, $atri_id, $value)";
        }
        $this->conn->executeQuery($sql);
    }

    public function insertUpdateLookupVal($inst_id, $atri_id, $lookup_id, $value)
    {
        $lv_id = -1;
        $lv_id = $this->getLookupValueID($lookup_id, $value);

        if ($lv_id == -1) {// error al obtenir el value del lookup
            echo "Value $value not found for atri_id=$atri_id\n aborting!\n";
            die;
        }

        if ($this->existValue($inst_id, $atri_id)) {// update
            $sql = "update omp_values v
						set v.num_val=$value
						where v.inst_id=$inst_id
						and v.atri_id=$atri_id					  
						and v.num_val!=$value
						";
        } else {// insert
            $sql = "insert into omp_values (inst_id, atri_id, num_val)
						values ($inst_id, $atri_id, $value)";
        }
        $this->conn->executeQuery($sql);
    }

    public function insertUpdateNumVal($inst_id, $atri_id, $value)
    {
        $value = $this->conn->quote($value);
        if ($this->existValue($inst_id, $atri_id)) {// update
            $sql = "update omp_values v
						set v.num_val=$value
						where v.inst_id=$inst_id
						and v.atri_id=$atri_id					  
						and v.num_val!=$value
						";
        } else {// insert
            $sql = "insert into omp_values (inst_id, atri_id, num_val)
						values ($inst_id, $atri_id, $value)";
        }
        $this->conn->executeQuery($sql);
    }

    public function insertUpdateDateVal($inst_id, $atri_id, $value)
    {
        $value = $this->conn->quote($value);
        if ($this->existValue($inst_id, $atri_id)) {// update
            $sql = "update omp_values v
						set v.date_val=$value
						where v.inst_id=$inst_id
						and v.atri_id=$atri_id					  
						and v.date_val!=$value
						";
        } else {// insert
            $sql = "insert into omp_values (inst_id, atri_id, date_val)
						values ($inst_id, $atri_id, $value)";
        }
        $this->conn->executeQuery($sql);
    }

    public function insertUpdateImageVal($inst_id, $atri_id, $value)
    {
        if (substr($value, 0, 7) == 'http://' || substr($value, 0, 8) == 'https://') {
            $file_name = basename($value);

            if (stripos($file_name, '.') === false) {
                $file_name = $file_name . '.png';
            }

            $img_file = $this->file_base . $this->url_base . 'downloaded/' . $file_name;
            if (!file_exists($img_file)) {
                file_put_contents($img_file, file_get_contents($value));
            }

            if (!file_exists($img_file)) {
                die("No existe el fichero " . $img_file . ", error!\n");
            }

            list($width, $height) = getimagesize($img_file);
            $value = 'downloaded/' . $file_name;
        } else {
            if (!file_exists($this->file_base . $value)) {
                die("No existe el fichero " . $this->file_base . $value . ", error!\n");
            }

            list($width, $height) = getimagesize($this->file_base . $value);
        }

        $value = $this->conn->quote($this->url_base . $value);
        if ($this->existValue($inst_id, $atri_id)) {// update
            $sql = "update omp_values v
						set v.text_val=$value
						, img_info='$width.$height'
						where v.inst_id=$inst_id
						and v.atri_id=$atri_id					  
						and v.text_val!=$value
						";
        } else {// insert
            $sql = "insert into omp_values (inst_id, atri_id, text_val, img_info)
						values ($inst_id, $atri_id, $value, '$width.$height')";
        }
        $this->conn->executeQuery($sql);
    }

    public function insertUpdateRemoteImageVal($inst_id, $atri_id, $value)
    {
        if (substr($value, 0, 7) == 'http://' || substr($value, 0, 8) == 'https://') {
            if (substr($value, 0, 23) == 'https://lorempixel.com/') {
                $path = parse_url($value, PHP_URL_PATH);
                //echo "$path\n";
                $arr_path = explode('/', $path);
                //print_r($arr_path);
                $width = $arr_path[1];
                $height = $arr_path[2];
            } elseif (substr($value, 0, 27) == 'https://www.dummyimage.com/') {
                $path = parse_url($value, PHP_URL_PATH);
                //echo "$path\n";
                $arr_path = explode('/', $path);
                //print_r($arr_path);
                $medidas=$arr_path[1];
                $arr_medidas=explode('x', $medidas);
                //print_r($arr_medidas);
                $width = $arr_medidas[0];
                $height = $arr_medidas[1];
            } else {
                list($width, $height) = getimagesize($value);
            }
        } else {
            return $this->insertUpdateImageVal($inst_id, $atri_id, $value);
        }

        $value = $this->conn->quote($value);
        if ($this->existValue($inst_id, $atri_id)) {// update
            $sql = "update omp_values v
						set v.text_val=$value
						, img_info='$width.$height'
						where v.inst_id=$inst_id
						and v.atri_id=$atri_id					  
						and v.text_val!=$value
						";
        } else {// insert
            $sql = "insert into omp_values (inst_id, atri_id, text_val, img_info)
						values ($inst_id, $atri_id, $value, '$width.$height')";
        }
        $this->conn->executeQuery($sql);
    }

    public function getLanguagesFromAttributes()
    {
        $sql = 'select language
				from omp_attributes
				group by language';

        $row = $this->fetchAll($sql);
        if (!$row) {
            return null;
        }
        return $row;

        return $this->conn->executeQuery($sql);
    }

    public function updateStatus($inst_id, $status = 'O')
    {
        if (!$this->existInstance($inst_id)) {
            return false;
        }

        $status = $this->conn->quote($status);

        
        $sql = "update omp_instances
				set status=$status
				, update_date=now()
				where id=$inst_id";
        $this->conn->executeQuery($sql);

        return $inst_id;
    }
}
