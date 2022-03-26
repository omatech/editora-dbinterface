<?php

/**
 * Created by Omatech
 * Date: 26/04/18 12:24
 */

namespace Omatech\Editora\Generator;

use Omatech\Editora\Clear\Clear;
use Omatech\Editora\DBInterfaceBase;
use Omatech\Editora\Utils\BcryptHasher;
use Omatech\Editora\Utils\Strings;

class Generator extends DBInterfaceBase
{
    protected $data;
    protected $queries;
    protected $users_passwords;

    public function __construct($conn, $params = array())
    {
        parent::__construct($conn, $params);
    }

    public function getQueries()
    {
        return $this->queries;
    }

    public function getFinalData()
    {
        return $this->data;
    }

    public function fromEnumToVarchar($table, $columns_array)
    {
        $sql = "show columns from $table";
        $rows = $this->fetchAll($sql);
        $changes = 0;
        foreach ($rows as $row) {
            if (in_array($row['Field'], $columns_array)) {
                if (substr($row['Type'], 0, 4) == 'enum') {
                    $sql = "alter table $table modify " . $row['Field'] . " varchar(10)\n";
                    $this->conn->executeQuery($sql);
                    $changes++;
                }
            }
        }
        return $changes;
    }

    public function tryToCreateIndex($table, $num, $columns_array, $unique = false)
    {
        $changes = 0;
        $index_name = $table . '_n' . $num;
        $unique_flag = '';
        if ($unique) {
            $index_name = $table . '_u' . $num;
            $unique_flag = ' unique ';
        }
        $index_name = $sql = "create $unique_flag index $index_name on $table (" . implode(',', $columns_array) . ") ";
        try {
            $this->conn->executeQuery($sql);
            echo "Created!\n";
            $changes++;
        } catch (\Exception $e) {
            echo "Already created!\n";
        }
        return $changes;
    }

    public function modernize()
    {
        $this->conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        $sm = $this->conn->getSchemaManager();
        $changes = 0;

        echo "Testing omp_attributes table\n";
        $changes += $this->fromEnumToVarchar('omp_attributes', ['type', 'language']);

        echo "Testing omp_class_attributes table\n";
        $changes += $this->fromEnumToVarchar('omp_class_attributes', ['caption_position', 'mandatory', 'detail']);

        echo "Testing omp_instances table\n";
        $changes += $this->fromEnumToVarchar('omp_instances', ['status']);

        echo "Testing omp_niceurl table\n";
        $changes += $this->fromEnumToVarchar('omp_niceurl', ['language']);

        echo "Testing omp_relations table\n";
        $changes += $this->fromEnumToVarchar('omp_relations', ['language', 'order_type']);

        echo "Testing omp_static_text table\n";
        $changes += $this->fromEnumToVarchar('omp_static_text', ['language']);

        echo "Testing omp_users table\n";
        $changes += $this->fromEnumToVarchar('omp_users', ['language']);

        $table = 'omp_instances';
        $sql = "show columns from $table";
        $rows = $this->fetchAll($sql);

        $order_string_found = false;
        $order_date_found = false;
        $external_id_found = false;
        $batch_id_found = false;
        foreach ($rows as $row) {
            if ($row['Field'] == 'order_string') {
                $order_string_found = true;
            }
            if ($row['Field'] == 'order_date') {
                $order_date_found = true;
            }
            if ($row['Field'] == 'external_id') {
                $external_id_found = true;
            }
            if ($row['Field'] == 'batch_id') {
                $batch_id_found = true;
            }
        }
        if (!$order_string_found) {
            $sql = "alter table $table add column order_string varchar(250) default null\n";
            $this->conn->executeQuery($sql);
            $sql = "alter table $table add key omp_instances_n7 (order_string)\n";
            $this->conn->executeQuery($sql);
            $changes++;
        }
        if (!$order_date_found) {
            $sql = "alter table $table add column order_date datetime default null\n";
            $this->conn->executeQuery($sql);
            $changes++;
        }
        if (!$external_id_found) {
            $sql = "alter table $table add column external_id varchar(250) default null\n";
            $this->conn->executeQuery($sql);
            $changes++;
        }
        if (!$external_id_found) {
            $sql = "alter table $table add column batch_id varchar(250) default null\n";
            $this->conn->executeQuery($sql);
            $changes++;
        }


        $sql = "show columns from omp_search";
        $rows = $this->fetchAll($sql);

        $title_found = false;
        foreach ($rows as $row) {
            if ($row['Field'] == 'title') {
                $title_found = true;
            }
        }
        if (!$title_found) {
            $sql = "alter table omp_search add column title varchar(250) default null\n";
            $this->conn->executeQuery($sql);
            $changes++;
        }

        $sql = "show columns from omp_attributes";
        $rows = $this->fetchAll($sql);

        $params_found = false;
        foreach ($rows as $row) {
            if ($row['Field'] == 'params') {
                $params_found = true;
            }
        }
        if (!$params_found) {
            $sql = "alter table omp_attributes add column params text default null\n";
            $this->conn->executeQuery($sql);
            $changes++;
        }

        /* Column json_val in omp_values */
        $sql = "show columns from omp_values";
        $rows = $this->fetchAll($sql);

        $json_val_found = false;
        foreach ($rows as $row) {
            if ($row['Field'] == 'json_val') {
                $json_val_found = true;
            }
        }
        if (!$json_val_found) {
            $sql = "alter table omp_values add column json_val TEXT default null\n";
            $this->conn->executeQuery($sql);
            $changes++;
            echo "Create column json_val in omp_values table\n";
        }

        $changes += $this->tryToCreateIndex('omp_attributes', 1, ['tag']);

        $changes += $this->tryToCreateIndex('omp_class_attributes', 1, ['class_id']);
        $changes += $this->tryToCreateIndex('omp_class_attributes', 2, ['atri_id']);
        $changes += $this->tryToCreateIndex('omp_class_attributes', 3, ['rel_id']);
        $changes += $this->tryToCreateIndex('omp_class_attributes', 4, ['tab_id']);

        $changes += $this->tryToCreateIndex('omp_classes', 1, ['name'], true);
        $changes += $this->tryToCreateIndex('omp_classes', 1, ['tag']);
        $changes += $this->tryToCreateIndex('omp_classes', 2, ['grp_id']);

        $changes += $this->tryToCreateIndex('omp_instances', 1, ['class_id']);
        $changes += $this->tryToCreateIndex('omp_instances', 2, ['publishing_begins', 'publishing_ends']);
        $changes += $this->tryToCreateIndex('omp_instances', 3, ['status']);
        $changes += $this->tryToCreateIndex('omp_instances', 4, ['key_fields']);
        $changes += $this->tryToCreateIndex('omp_instances', 5, ['external_id']);
        $changes += $this->tryToCreateIndex('omp_instances', 6, ['batch_id']);
        $changes += $this->tryToCreateIndex('omp_instances', 7, ['order_string']);
        $changes += $this->tryToCreateIndex('omp_instances', 8, ['order_date']);

        $changes += $this->tryToCreateIndex('omp_instances_backup', 1, ['inst_id', 'language']);

        $changes += $this->tryToCreateIndex('omp_instances_cache', 1, ['inst_id', 'language'], true);

        $changes += $this->tryToCreateIndex('omp_lookups_values', 1, ['lookup_id', 'ordre']);

        $changes += $this->tryToCreateIndex('omp_niceurl', 1, ['niceurl', 'language'], true);

        $changes += $this->tryToCreateIndex('omp_relation_instances', 1, ['child_inst_id']);
        $changes += $this->tryToCreateIndex('omp_relation_instances', 2, ['parent_inst_id']);
        $changes += $this->tryToCreateIndex('omp_relation_instances', 3, ['external_id']);
        $changes += $this->tryToCreateIndex('omp_relation_instances', 4, ['batch_id']);
        $changes += $this->tryToCreateIndex('omp_relation_instances', 5, ['rel_id']);

        $changes += $this->tryToCreateIndex('omp_relations', 1, ['parent_class_id']);
        $changes += $this->tryToCreateIndex('omp_relations', 2, ['child_class_id']);
        $changes += $this->tryToCreateIndex('omp_relations', 3, ['name']);
        $changes += $this->tryToCreateIndex('omp_relations', 4, ['tag']);

        $changes += $this->tryToCreateIndex('omp_roles_classes', 1, ['class_id']);
        $changes += $this->tryToCreateIndex('omp_roles_classes', 2, ['rol_id']);

        $changes += $this->tryToCreateIndex('omp_static_text', 1, ['text_key', 'language']);

        $changes += $this->tryToCreateIndex('omp_user_instances', 1, ['user_id', 'tipo_acceso']);

        $changes += $this->tryToCreateIndex('omp_users', 1, ['username'], true);

        $changes += $this->tryToCreateIndex('omp_values', 1, ['inst_id', 'atri_id']);
        $changes += $this->tryToCreateIndex('omp_values', 2, ['date_val']);
        $changes += $this->tryToCreateIndex('omp_values', 3, ['num_val']);

        return $changes;
    }

    public function resetPasswords($length = 8)
    {
        $this->conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        $sm = $this->conn->getSchemaManager();
        $columns = $sm->listTableColumns('omp_users');
        $passwordColumn = $columns['password'];
        $hashedPasswordColumn = $columns['hashed_password'];

        $sqlAlterTable = '';

        if ($passwordColumn->getLength() != 100) {
            $sqlAlterTable .= 'ALTER TABLE omp_users MODIFY password VARCHAR (100) NOT NULL;';
            echo "- password column has been set to 100 characters\n";
        }

        if (empty($hashedPasswordColumn)) {
            $sqlAlterTable .= 'ALTER TABLE omp_users ADD hashed_password TINYINT (1) DEFAULT 0;';
            echo "- hashed_password column added\n";
        }

        if ($sqlAlterTable != '') {
            $this->conn->exec($sqlAlterTable);
        }

        $sql = "select username, id from omp_users";
        $users = $this->fetchAll($sql);

        foreach ($users as $user) {
            $user_id = $user['id'];
            //echo "User $user_id\n";
            $hasher = new BcryptHasher();
            $password = Strings::generateStrongPassword($length);
            $hashed_password = $hasher->make($password);

            $sql = "update omp_users
			set password=" . $this->conn->quote($hashed_password) . "
			, hashed_password=1
			where id=$user_id
			";
            $this->conn->executeQuery($sql);
            $this->users_passwords[$user['username']] = array($password, $hashed_password);
        }
    }

    public function encryptPasswords()
    {
        $this->conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        $sm = $this->conn->getSchemaManager();
        $columns = $sm->listTableColumns('omp_users');
        $passwordColumn = $columns['password'];
        $hashedPasswordColumn = $columns['hashed_password'];

        $sqlAlterTable = '';

        if ($passwordColumn->getLength() != 100) {
            $sqlAlterTable .= 'ALTER TABLE omp_users MODIFY password VARCHAR (100) NOT NULL;';
            echo "- password column has been set to 100 characters\n";
        }

        if (empty($hashedPasswordColumn)) {
            $sqlAlterTable .= 'ALTER TABLE omp_users ADD hashed_password TINYINT (1) DEFAULT 0;';
            echo "- hashed_password column added\n";
        }

        if ($sqlAlterTable != '') {
            $this->conn->exec($sqlAlterTable);
        }

        $sql = "select username, password, id from omp_users where hashed_password = 0";
        $users = $this->fetchAll($sql);

        foreach ($users as $user) {
            $user_id = $user['id'];
            //echo "User $user_id\n";
            $hasher = new BcryptHasher();
            $old_password = $user['password'];
            $hashed_password = $hasher->make($user['password']);

            $sql = "update omp_users
			set password=" . $this->conn->quote($hashed_password) . "
			, hashed_password=1
			where id=$user_id
			";
            $this->conn->executeQuery($sql);
            $this->users_passwords[$user['username']] = array($old_password, $hashed_password);
        }
    }

    public function checkPassword($user, $hassed_password)
    {
        $user = $this->conn->quote($user);
        $hassed_password = $this->conn->quote($hassed_password);

        $sql = "select count(*) num
		from omp_users
		where username=$user
		and password=$hassed_password
		";
        $num = $this->fetchColumn($sql);
        return $num == 1;
    }

    /**
     * @param $data
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function createEditora(array $data)
    {
        $this->data = $this->editoraPrepareData($data);


        if (!$this->validateData($this->data)) {
            return false;
        }

        extract(
            $this->data,
            EXTR_OVERWRITE
        );


        $this->queries = array();

        $editora_structure = file_get_contents(__DIR__ . '/../../../../data/editora.sql');
        array_push($this->queries, $editora_structure);

        array_push($this->queries, "delete from omp_attributes;");
        array_push($this->queries, "delete from omp_class_attributes;");
        array_push($this->queries, "delete from omp_class_groups;");
        array_push($this->queries, "delete from omp_classes;");
        array_push($this->queries, "delete from omp_lookups;");
        array_push($this->queries, "delete from omp_lookups_values;");
        array_push($this->queries, "delete from omp_relations;");
        array_push($this->queries, "delete from omp_roles;");
        array_push($this->queries, "delete from omp_roles_classes;");
        array_push($this->queries, "delete from omp_tabs;");

        if (isset($tabs) && is_array($tabs)) {
            foreach ($tabs as $key => $tab) {
                $this->create_tab($key, $tab, $key);
            }
        }

        $i = 2;
        if ($attributes_params) {

            // Creem l'atribut nom_intern
            $nom_intern_values = ['nom_intern', 'caption'=>['Nom intern', 'Nombre interno', 'Internal name']];
            $this->create_params_attribute($nomintern_id, 'S', 0, 'ALL', $nom_intern_values);

            foreach ($languages as $key_lang => $val_lang) {
                $niceurl_params = [$niceurl_name];
                $this->create_params_attribute($niceurl_id, 'Z', $key_lang, $val_lang, $niceurl_params);
                $this->create_tab($key_lang, $val_lang, $i++);
            }

            foreach ($attributes_order_string as $id => $val) {
                $this->create_params_attribute($id, 'B', 0, 'ALL', $val);
            }
            foreach ($attributes_order_date as $id => $val) {
                $this->create_params_attribute($id, 'E', 0, 'ALL', $val);
            }
            foreach ($attributes_color as $id => $val) {
                $this->create_params_attribute($id, 'O', 0, 'ALL', $val);
            }

            foreach ($attributes_geolocation as $id => $val) {
                $this->create_params_attribute($id, 'M', 0, 'ALL', $val);
            }

            foreach ($attributes_date as $id => $val) {
                $this->create_params_attribute($id, 'D', 0, 'ALL', $val);
            }

            foreach ($attributes_num as $id => $val) {
                $this->create_params_attribute($id, 'N', 0, 'ALL', $val);
            }

            foreach ($attributes_lookup as $id => $val) {
                $this->create_params_attribute($id, 'L', 0, 'ALL', $val);
            }


            foreach ($attributes_string as $id => $val) {
                $this->create_params_attribute($id, 'S', 0, 'ALL', $val);
            }

            foreach ($attributes_multi_lang_string as $id => $val) {
                foreach ($languages as $key_lang => $val_lang) {
                    $this->create_params_attribute($id, 'S', $key_lang, $val_lang, $val);
                }
            }

            foreach ($attributes_text as $id => $val) {
                $this->create_params_attribute($id, 'T', 0, 'ALL', $val);
            }

            foreach ($attributes_multi_lang_text as $id => $val) {
                foreach ($languages as $key_lang => $val_lang) {
                    $this->create_params_attribute($id, 'T', $key_lang, $val_lang, $val);
                }
            }

            foreach ($attributes_textarea as $id => $val) {
                $this->create_params_attribute($id, 'K', 0, 'ALL', $val);
            }

            foreach ($attributes_multi_lang_textarea as $id => $val) {
                foreach ($languages as $key_lang => $val_lang) {
                    $this->create_params_attribute($id, 'K', $key_lang, $val_lang, $val);
                }
            }

            foreach ($attributes_file as $id => $val) {
                $this->create_params_attribute($id, 'F', 0, 'ALL', $val);
            }

            foreach ($attributes_multi_lang_file as $id => $val) {
                foreach ($languages as $key_lang => $val_lang) {
                    $this->create_params_attribute($id, 'F', $key_lang, $val_lang, $val);
                }
            }

            foreach ($attributes_privatefile as $id => $val) {
                $this->create_params_attribute($id, 'P', 0, 'ALL', $val);
            }

            foreach ($attributes_multi_lang_privatefile as $id => $val) {
                foreach ($languages as $key_lang => $val_lang) {
                    $this->create_params_attribute($id, 'P', $key_lang, $val_lang, $val);
                }
            }

            foreach ($attributes_image as $id => $params) {
                $this->create_params_attribute($id, 'I', 0, 'ALL', $params);
            }

            foreach ($attributes_multi_lang_image as $id => $val) {
                foreach ($languages as $key_lang => $val_lang) {
                    $this->create_params_attribute($id, 'I', $key_lang, $val_lang, $val);
                }
            }

            foreach ($attributes_grid_image as $id => $val) {
                $this->create_params_attribute($id, 'H', 0, 'ALL', $val);
            }

            foreach ($attributes_video as $id => $val) {
                $this->create_params_attribute($id, 'Y', 0, 'ALL', $val);
            }

            foreach ($attributes_multi_lang_video as $id => $val) {
                foreach ($languages as $key_lang => $val_lang) {
                    $this->create_params_attribute($id, 'Y', $key_lang, $val_lang, $val);
                }
            }

            foreach ($attributes_url as $id => $val) {
                $this->create_params_attribute($id, 'U', 0, 'ALL', $val);
            }

            foreach ($attributes_multi_lang_url as $id => $val) {
                foreach ($languages as $key_lang => $val_lang) {
                    $this->create_params_attribute($id, 'U', $key_lang, $val_lang, $val);
                }
            }

            if (isset($attributes_json)) {
                foreach ($attributes_json as $id => $val) {
                    $this->create_params_attribute($id, 'J', 0, 'ALL', $val);
                }
            }
            if (isset($attributes_multi_lang_json)) {
                foreach ($attributes_multi_lang_json as $id => $val) {
                    foreach ($languages as $key_lang => $val_lang) {
                        $this->create_params_attribute($id, 'J', $key_lang, $val_lang, $val);
                    }
                }
            }


            $i = 1;
            foreach ($groups as $group_id => $group_values) {
                $name = $caption_ca = $caption_es = $caption_en =$this->key_to_title($group_values[0]);

                if (isset($group_values['caption'])) {
                    $caption = $group_values['caption'];
                    $caption_ca = $this->key_to_title($caption[0]);
                    $caption_es = $this->key_to_title(isset($caption[1]) ? $caption[1] : $caption[0]);
                    $caption_en = $this->key_to_title(isset($caption[2]) ? $caption[2] : $caption[0]);
                }
                $this->create_class_group($name, $group_id, $caption_ca, $caption_es, $caption_en);

                if (isset($group_values['classes'])) {
                    foreach ($group_values['classes'] as $class_id => $class_values) {
                        $key=$class_values[0];
                        $name = $caption_ca = $caption_es = $caption_en =$this->key_to_title($key);

                        if (isset($class_values['caption'])) {
                            $caption = $class_values['caption'];
                            $caption_ca = $this->key_to_title($caption[0]);
                            $caption_es = $this->key_to_title(isset($caption[1]) ? $caption[1] : $caption[0]);
                            $caption_en = $this->key_to_title(isset($caption[2]) ? $caption[2] : $caption[0]);
                        }
                        if (isset($class_values['editable']) && $class_values['editable']==false) {
                            $editable = 'N';
                        } else {
                            $editable = 'Y';
                        }


                        $this->create_class($class_id, $key, $group_id, $i++, $caption, $caption_ca, $caption_es, $caption_en, $editable);
                        $this->create_class_attribute($class_id, $nomintern_id, 0, 1, 1, 1, true, true);
                        ;


                        if (isset($class_values['seo_options']) && $class_values['seo_options']==true && isset($data['seo_attributes'])) {
                            $seo_attributes_in_class = explode(',', $data['seo_attributes'][0]);
                            $fila = $lang_fila = 1;

                            foreach ($seo_attributes_in_class as $atri_id) {
                                $atri_ids = explode('-', $atri_id);
                                $atri_id = $atri_ids[0];

                                if (stripos($atri_id, '*') !== false) {
                                    $atri_id = str_replace('*', '', $atri_id);
                                    $mandatory = true;
                                } else {
                                    $mandatory = false;
                                }


                                if (array_key_exists($atri_id, $this->data['original_localized_attributes'])) {// es un atribut localized
                                    foreach ($languages as $key_lang => $val_lang) {
                                        $this->create_class_attribute($class_id, $atri_id+$key_lang, 0, 100, $lang_fila, $key_lang, false, $mandatory);
                                    }
                                    $lang_fila++;
                                } else {
                                    $this->create_class_attribute($class_id, $atri_id, 0, 100, $fila, 1, false, $mandatory);
                                    $fila++;
                                }
                            }
                        }


                        if (isset($class_values['attributes'])) {
                            $filas = [1 => 2];
                            foreach ($languages as $key_lang => $val_lang) {
                                $filas[$key_lang] = 2;
                            }
                            $attributes_in_class = explode(',', $class_values['attributes'][0]);
                            foreach ($attributes_in_class as $atri_id) {
                                $atri_ids = explode('-', $atri_id);
                                $atri_id = $atri_ids[0];

                                if (stripos($atri_id, '*') !== false) {
                                    $atri_id = str_replace('*', '', $atri_id);
                                    $mandatory = true;
                                } else {
                                    $mandatory = false;
                                }

                                if (array_key_exists($atri_id, $this->data['original_localized_attributes'])) {// es un atribut localized
                                    foreach ($languages as $key_lang => $val_lang) {
                                        $this->create_class_attribute($class_id, $atri_id + $key_lang, 0, $key_lang, $filas[$key_lang], 1, false, $mandatory);
                                        $filas[$key_lang] = $filas[$key_lang] + 1;
                                    }
                                } else {
                                    if (!empty($atri_id)) {
                                        $this->create_class_attribute($class_id, $atri_id, 0, 1, $filas[1], 1, false, $mandatory);
                                        $filas[1] = $filas[1] + 1;
                                    }
                                }

                                if (array_key_exists(1, $atri_ids)) {
                                    $atri_id = $atri_ids[1];
                                    if (stripos($atri_id, '*') !== false) {
                                        $atri_id = str_replace('*', '', $atri_id);
                                        $mandatory = true;
                                    } else {
                                        $mandatory = false;
                                    }

                                    if (array_key_exists($atri_id, $this->data['original_localized_attributes'])) {// es un atribut localized
                                        foreach ($languages as $key_lang => $val_lang) {
                                            $this->create_class_attribute($class_id, $atri_id + $key_lang, 0, $key_lang, $filas[$key_lang] - 1, 2, false, $mandatory);
                                        }
                                    } else {
                                        if (!empty($atri_id)) {
                                            $this->create_class_attribute($class_id, $atri_id, 0, 1, $filas[1] - 1, 2, false, $mandatory);
                                        }
                                    }
                                }
                            }
                            $global_filas[$class_id] = $filas;
                        }

                        $need_url_nice = $groups[$group_id]; //TODO ????

                        if (isset($class_values['relations'])) {
                            foreach ($class_values['relations'] as $relation_id => $relation_values) {
                                $this->create_params_relation($relation_id, $class_id, $relation_values);
                                if (isset($global_filas[$class_id])) {// per si no hem creat tots els class attributes encara
                                    if (!isset($global_columnas[$class_id][1]) || $global_columnas[$class_id][1] == 1) {// no tenemos la columna previamente (caso inicial) o era 1
                                        $this->create_class_attribute($class_id, 0, $relation_id, 1, $global_filas[$class_id][1], 1, false, false);
                                        $global_columnas[$class_id][1] = 2;
                                    } else {// teniamos columna previamente y era la columna2, reseteamos
                                        $this->create_class_attribute($class_id, 0, $relation_id, 1, $global_filas[$class_id][1] ++, 2, false, false);
                                        $global_columnas[$class_id][1] = 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // Creem l'atribut nom_intern
            $this->create_attribute($nomintern_id, $nomintern_name, 'S', 0, 'ALL', 0, $nomintern_caption);

            foreach ($languages as $key_lang => $val_lang) {
                $this->create_attribute($niceurl_id, $niceurl_name, 'Z', $key_lang, $val_lang);
                $this->create_tab($key_lang, $val_lang, $i++);
            }

            foreach ($lookups as $lookup_key => $lookup) {
                $arr_lookup_info = explode(',', $lookup_key);
                $lookup_id = $arr_lookup_info[0];
                $lookup_name = $arr_lookup_info[1];
                array_push($this->queries, "insert into omp_lookups (id, name, type, default_id) values ($lookup_id, '$lookup_name', 'L', 0);");
                $i = 0;
                foreach ($lookup as $value_key => $value) {
                    array_push($this->queries, "insert into omp_lookups_values (id, lookup_id, ordre, value, caption_ca, caption_es, caption_en) values ($value_key, $lookup_id, $i, '$value[0]', '$value[1]', '$value[2]', '$value[3]');");
                    if ($i == 0) {
                        array_push($this->queries, "update omp_lookups set default_id='" . $value_key . "' where id=$lookup_id;\n");
                    }
                    $i++;
                }
            }
            foreach ($attributes_lookup as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $arr_val = explode(',', $tag);
                    $lookup_name = $arr_val[0];
                    $lookup_id = $arr_val[1];

                    $this->create_attribute($key, $lookup_name, 'L', 0, 'ALL', $lookup_id, $val);
                } else {
                    $arr_val = explode(',', $val);
                    $lookup_name = $arr_val[0];
                    $lookup_id = $arr_val[1];
                    $this->create_attribute($key, $lookup_name, 'L', 0, 'ALL', $lookup_id);
                }
            }

            foreach ($attributes_multi_lang_string as $key => $val) {
                //print_r($val);
                if (is_array($val)) {
                    // take the first element that is tag and remove the first element, pass the rest of the array as captions
                    $tag = $val[0];
                    array_shift($val);
                    foreach ($languages as $key_lang => $val_lang) {
                        $this->create_attribute($key, $tag, 'S', $key_lang, $val_lang, 0, $val);
                    }
                } else {
                    foreach ($languages as $key_lang => $val_lang) {
                        $this->create_attribute($key, $val, 'S', $key_lang, $val_lang);
                    }
                }
            }

            foreach ($attributes_multi_lang_textarea as $key => $val) {
                if (is_array($val)) {
                    // take the first element that is tag and remove the first element, pass the rest of the array as captions
                    $tag = $val[0];
                    array_shift($val);
                    foreach ($languages as $key_lang => $val_lang) {
                        $this->create_attribute($key, $tag, 'K', $key_lang, $val_lang, 0, $val);
                    }
                } else {
                    foreach ($languages as $key_lang => $val_lang) {
                        $this->create_attribute($key, $val, 'K', $key_lang, $val_lang);
                    }
                }
            }

            foreach ($attributes_multi_lang_image as $key => $val) {
                if (is_array($val)) {
                    // take the first element that is tag and remove the first element, pass the rest of the array as captions
                    $tag = $val[0];
                    array_shift($val);
                    foreach ($languages as $key_lang => $val_lang) {
                        $this->create_attribute($key, $tag, 'I', $key_lang, $val_lang, 0, $val);
                    }
                } else {
                    foreach ($languages as $key_lang => $val_lang) {
                        $this->create_attribute($key, $val, 'I', $key_lang, $val_lang);
                    }
                }
            }

            foreach ($attributes_multi_lang_file as $key => $val) {
                if (is_array($val)) {
                    // take the first element that is tag and remove the first element, pass the rest of the array as captions
                    $tag = $val[0];
                    array_shift($val);
                    foreach ($languages as $key_lang => $val_lang) {
                        $this->create_attribute($key, $tag, 'F', $key_lang, $val_lang, 0, $val);
                    }
                } else {
                    foreach ($languages as $key_lang => $val_lang) {
                        $this->create_attribute($key, $val, 'F', $key_lang, $val_lang);
                    }
                }
            }

            if (isset($attributes_multi_lang_url)) {
                foreach ($attributes_multi_lang_url as $key => $val) {
                    if (is_array($val)) {
                        // take the first element that is tag and remove the first element, pass the rest of the array as captions
                        $tag = $val[0];
                        array_shift($val);
                        foreach ($languages as $key_lang => $val_lang) {
                            $this->create_attribute($key, $tag, 'U', $key_lang, $val_lang, 0, $val);
                        }
                    } else {
                        foreach ($languages as $key_lang => $val_lang) {
                            $this->create_attribute($key, $val, 'U', $key_lang, $val_lang);
                        }
                    }
                }
            }

            if (isset($attributes_multi_lang_video)) {
                foreach ($attributes_multi_lang_video as $key => $val) {
                    if (is_array($val)) {
                        // take the first element that is tag and remove the first element, pass the rest of the array as captions
                        $tag = $val[0];
                        array_shift($val);
                        foreach ($languages as $key_lang => $val_lang) {
                            $this->create_attribute($key, $tag, 'Y', $key_lang, $val_lang, 0, $val);
                        }
                    } else {
                        foreach ($languages as $key_lang => $val_lang) {
                            $this->create_attribute($key, $val, 'Y', $key_lang, $val_lang);
                        }
                    }
                }
            }

            foreach ($attributes_textarea as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'K', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'K');
                }
            }

            foreach ($attributes_text as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'T', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'T');
                }
            }

            foreach ($attributes_multi_lang_text as $key => $val) {
                if (is_array($val)) {
                    // take the first element that is tag and remove the first element, pass the rest of the array as captions
                    $tag = $val[0];
                    array_shift($val);
                    foreach ($languages as $key_lang => $val_lang) {
                        $this->create_attribute($key, $tag, 'T', $key_lang, $val_lang, 0, $val);
                    }
                } else {
                    foreach ($languages as $key_lang => $val_lang) {
                        $this->create_attribute($key, $val, 'T', $key_lang, $val_lang);
                    }
                }
            }

            foreach ($attributes_file as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'F', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'F');
                }
            }

            foreach ($attributes_order_string as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'B', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'B');
                }
            }

            foreach ($attributes_order_date as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'E', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'E');
                }
            }

            foreach ($attributes_string as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'S', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'S');
                }
            }

            foreach ($attributes_color as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'O', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'O');
                }
            }

            foreach ($attributes_image as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'I', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'I');
                }
            }

            foreach ($attributes_grid_image as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'H', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'H');
                }
            }

            foreach ($attributes_geolocation as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'M', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'M');
                }
            }

            foreach ($attributes_date as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'D', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'D');
                }
            }

            foreach ($attributes_num as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'N', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'N');
                }
            }

            foreach ($attributes_video as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'Y', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'Y');
                }
            }

            foreach ($attributes_url as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'U', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'U');
                }
            }


            foreach ($attributes_json as $key => $val) {
                if (is_array($val)) {
                    $tag = $val[0];
                    array_shift($val);
                    $this->create_attribute($key, $tag, 'J', 0, 'ALL', 0, $val);
                } else {
                    $this->create_attribute($key, $val, 'J');
                }
            }

            foreach ($images_sizes as $key_size => $size) {
                $arr_sizes = explode('x', $size);
                if (isset($arr_sizes[0]) && !empty($arr_sizes[0])) {
                    array_push($this->queries, "update omp_attributes set img_width=" . $arr_sizes[0] . " where id=$key_size;");
                }

                if (isset($arr_sizes[1]) && !empty($arr_sizes[1])) {
                    array_push($this->queries, "update omp_attributes set img_height=" . $arr_sizes[1] . " where id=$key_size;");
                }

                foreach ($languages as $key_lang => $val_lang) {
                    $key_lang_size = $key_size + $key_lang;
                    if (isset($arr_sizes[0]) && !empty($arr_sizes[0])) {
                        array_push($this->queries, "update omp_attributes set img_width=" . $arr_sizes[0] . " where id=$key_lang_size;");
                    }

                    if (isset($arr_sizes[1]) && !empty($arr_sizes[1])) {
                        array_push($this->queries, "update omp_attributes set img_height=" . $arr_sizes[1] . " where id=$key_lang_size;");
                    }
                }
            }

            if (isset($groups) && isset($classes)) {// new method
                array_push($this->queries, "delete from omp_class_groups;");
                $i = 1;
                foreach ($groups as $key => $val) {
                    if (is_array($val)) {
                        $caption = null;

                        $caption_ca = $this->key_to_title(isset($val[1]) ? $val[1] : $key);
                        $caption_es = $this->key_to_title(isset($val[2]) ? $val[2] : $key);
                        $caption_en = $this->key_to_title(isset($val[3]) ? $val[3] : $key);
                        $val = $this->key_to_title($val[0]);

                        $this->create_class_group($key, $i++, $caption_ca, $caption_es, $caption_en);
                    } else {
                        $this->create_class_group($key, $i++);
                    }
                }

                $i = 1;
                foreach ($classes as $group_key => $group_val) {
                    foreach ($group_val as $key => $val) {
                        if (is_array($val)) {
                            $caption = null;
                            $caption_ca = $this->key_to_title(isset($val[1]) ? $val[1] : $val[0]);
                            $caption_es = $this->key_to_title(isset($val[2]) ? $val[2] : $val[0]);
                            $caption_en = $this->key_to_title(isset($val[3]) ? $val[3] : $val[0]);
                            //$val = $this->key_to_title($val[0]);

                            $this->create_class($key, $val[0], $groups[$group_key], $i++, $caption, $caption_ca, $caption_es, $caption_en);
                        } else {
                            $caption = $val;
                            $this->create_class($key, $val, $groups[$group_key], $i++, $caption);
                        }

                        $this->create_class_attribute($key, $nomintern_id, 0, 1, 1, 1, true, true);
                        $need_url_nice = $groups[$group_key]; //TODO ????
                    }
                }
            } else {// old method
                $i = 0;
                foreach ($classes_with_url_nice as $key => $val) {
                    $this->create_class($key, $val, 1, $i++);
                    $this->create_class_attribute($key, $nomintern_id, 0, 1, 1, 1, true, true);
                    foreach ($languages as $key_lang => $val_lang) {
                        //echo "1. create_class_attribute $key, $key_lang+$niceurl_id, 0, $key_lang, 1, 2, false, false\n";
                        $this->create_class_attribute($key, $key_lang + $niceurl_id, 0, $key_lang, 1, 2, false, false);
                    }
                }

                $i = 0;
                foreach ($other_classes as $key => $val) {
                    $this->create_class_attribute($key, $nomintern_id, 0, 1, 1, 1, true, true);
                    $this->create_class($key, $val, 2, $i++);
                }
            }

            foreach ($attributes_classes as $key => $val) {
                $filas = [1 => 2];
                foreach ($languages as $key_lang => $val_lang) {
                    $filas[$key_lang] = 2;
                }

                $attributes_in_class = explode(',', $val);
                foreach ($attributes_in_class as $atri_id) {
                    $atri_ids = explode('-', $atri_id);
                    $atri_id = $atri_ids[0];

                    if (stripos($atri_id, '*') !== false) {
                        $atri_id = str_replace('*', '', $atri_id);
                        $mandatory = true;
                    } else {
                        $mandatory = false;
                    }

                    if (array_key_exists($atri_id, $this->data['original_localized_attributes'])) {// es un atribut localized
                        foreach ($languages as $key_lang => $val_lang) {
                            //echo "2. create_class_attribute $key, $atri_id+$key_lang, 0, $key_lang, $filas[$key_lang], 1, false, $mandatory\n";
                            $this->create_class_attribute($key, $atri_id + $key_lang, 0, $key_lang, $filas[$key_lang], 1, false, $mandatory);
                            $filas[$key_lang] = $filas[$key_lang] + 1;
                        }
                    } else {
                        //echo "3. create_class_attribute $key, $atri_id, 0, 1, $filas[1], 1, false, $mandatory\n";
                        $this->create_class_attribute($key, $atri_id, 0, 1, $filas[1], 1, false, $mandatory);
                        $filas[1] = $filas[1] + 1;
                    }

                    if (array_key_exists(1, $atri_ids)) {
                        $atri_id = $atri_ids[1];
                        if (stripos($atri_id, '*') !== false) {
                            $atri_id = str_replace('*', '', $atri_id);
                            $mandatory = true;
                        } else {
                            $mandatory = false;
                        }

                        if (array_key_exists($atri_id, $this->data['original_localized_attributes'])) {// es un atribut localized
                            foreach ($languages as $key_lang => $val_lang) {
                                //echo "4. create_class_attribute $key, $atri_id+$key_lang, 0, $key_lang, $filas[$key_lang]-1, 2, false, $mandatory\n";
                                $this->create_class_attribute($key, $atri_id + $key_lang, 0, $key_lang, $filas[$key_lang] - 1, 2, false, $mandatory);
                            }
                        } else {
                            //echo "5. create_class_attribute $key, $atri_id, 0, 1, $filas[1]-1, 2, false, $mandatory\n";
                            $this->create_class_attribute($key, $atri_id, 0, 1, $filas[1] - 1, 2, false, $mandatory);
                        }
                    }
                }
                $global_filas[$key] = $filas;
            }

            foreach ($relations as $key => $val) {
                $arr_ids = explode(',', $val);
                $parent = array_shift($arr_ids);
                $name = $this->get_relation_name($key, $parent, $arr_ids);
                $childs = implode(',', $arr_ids);
                $this->create_relation($key, $parent, $childs, $name);
                if (isset($global_filas[$parent])) {// per si no hem creat tots els class attributes encara
                    if (!isset($global_columnas[$parent][1]) || $global_columnas[$parent][1] == 1) {// no tenemos la columna previamente (caso inicial) o era 1
                        $this->create_class_attribute($parent, 0, $key, 1, $global_filas[$parent][1], 1, false, false);
                        $global_columnas[$parent][1] = 2;
                    } else {// teniamos columna previamente y era la columna2, reseteamos
                        $this->create_class_attribute($parent, 0, $key, 1, $global_filas[$parent][1] ++, 2, false, false);
                        $global_columnas[$parent][1] = 1;
                    }
                }
            }
        }

        if (isset($roles) && is_array($roles)) {
            foreach ($roles as $aRole) {
                if (!isset($aRole['id']) || !isset($aRole['name'])) {
                    //TODO throw
                }

                array_push($this->queries, 'INSERT INTO `omp_roles` VALUES (\'' . $aRole['id'] . '\', \'' . $aRole['name'] . '\', \'Y\');');
            }
        } else {
            //TODO throw
        }


        
        $create_users=true;
        if (isset($data['create_users']) && $data['create_users']==false) {
            $create_users=false;
        }

        if ($create_users) {
            foreach ($users as $user) {
                $hasher = new BcryptHasher();
                //$password = substr(md5(rand()), 0, 7);
                $password = Strings::generateStrongPassword(8);
                $hashed_password = $hasher->make($password);
    
                $username = $this->conn->quote($user[0]);
                $complete_name = $this->conn->quote($user[1]);
    
                array_push($this->queries, "insert ignore into omp_users (username, password, complete_name, language, rol_id, tipus) values ($username, '$hashed_password', $complete_name, '$user[2]', '$user[3]', '$user[4]');");
                $this->users_passwords[$user[0]] = array($password, $hashed_password);
            }
        }


        //Clear generic tables
        $Clear = new Clear($this->conn, array());
        $Clear->truncateTables($data['truncate_users']);

        $this->startTransaction();

        foreach ($this->queries as $aQuery) {
            $this->conn->executeQuery($aQuery);
        }

        // Creem les instancies home i global si no les teniem
        $loader = new \Omatech\Editora\Loader\Loader($this->conn, $this->params);
        $ret = $loader->ExistingInstanceIsDifferent(1, 'Home', ['nom_intern' => 'Home'], 'O', $difference, $attr_difference);
        if ($ret) {
            $loader->insertInstanceForcingID(1, 10, 'Home', ['nom_intern' => 'Home']);

            foreach ($languages as $key_lang => $val_lang) {
                $loader->insertUrlNice('home', 1, $val_lang);
                $loader->insertUpdateTextVal(1, 2, 'home');
            }
        }
        $ret = $loader->ExistingInstanceIsDifferent(2, 'GLOBAL', ['nom_intern' => 'GLOBAL'], 'O', $difference, $attr_difference);
        if ($ret) {
            $loader->insertInstanceForcingID(2, 1, 'GLOBAL', ['nom_intern' => 'GLOBAL']);
        }

        return true;
    }

    public function get_users_passwords()
    {
        return $this->users_passwords;
    }

    public function editoraDefaultNomInternId()
    {
        return 1;
    }

    public function editoraDefaultNomInternName()
    {
        return 'nom_intern';
    }

    public function editoraDefaultNomInternCaption()
    {
        return ['Nom intern', 'Nombre interno', 'Internal name'];
    }

    public function editoraDefaultRoles()
    {
        return array(
            array('id' => 1, 'name' => 'admin'),
            array('id' => 2, 'name' => 'user'),
        );
    }

    public function editoraPrepareData($data)
    {
        $defaultData = $this->editoraDefaultData();

        $process_variables = [
            'attributes_params' => false,
            'localized_attributes' => array(),
            'simple_attributes' => array(),
            'original_localized_attributes' => array(),
            'global_filas' => array(),
            'classes_with_url_nice' => array(),
            'other_classes' => array(),
            'seo_attributes' => array()];


        foreach ($defaultData as $aDefaultDataKey => $aDefaultDataValue) {
            if (empty($aDefaultDataValue)) {
                continue;
            }

            if (isset($data[$aDefaultDataKey])) {
                if (!is_array($aDefaultDataValue)) {
                    $data[$aDefaultDataKey] = $aDefaultDataValue;
                } else {
                    $data[$aDefaultDataKey] = array_merge($aDefaultDataValue, $data[$aDefaultDataKey]);
                }

                unset($defaultData[$aDefaultDataKey]);
            }
        }

        return array_merge($defaultData, $process_variables, $data);
    }

    public function validateData($data)
    {
        return !(
            !is_array($data) ||
            empty($data['users']) ||
            !is_array($data['users'])
        );
    }

    // funcions auxiliars

    public function create_relation($id, $parent, $children, $name)
    {
        $children = trim($children);
        if (is_array($name)) {
            $nice_name = $name[0];
            $nice_name_ca = isset($name[2]) ? $name[2] : $name[0];
            $nice_name_es = isset($name[3]) ? $name[3] : $name[0];
            $name_tag = $name[1];
        } else {
            $nice_name = $nice_name_ca = $nice_name_es = $this->key_to_title($name);
            $name_tag = $name;
        }

        if (stripos($children, ',') !== false) {// multiple children
            $single_child = "0";
            $multiple_children = $children;
        } else {// single child
            $single_child = $children;
            $multiple_children = '';
        }

        array_push($this->queries, "insert into omp_relations (id, name, caption, language, tag, parent_class_id, child_class_id, multiple_child_class_id, order_type, join_icon, create_icon, join_massive, caption_ca, caption_es, caption_en, autocomplete)
			values($id, '$name_tag', '$nice_name', 'ALL', '$name_tag', $parent, $single_child, '$multiple_children', 'M', 'Y', 'Y', 'N', '$nice_name_ca', '$nice_name_es', '$nice_name', 'Y');");
    }

    public function create_params_relation($key, $class_id, $params)
    {
        $id = $key;
        $parent = $class_id;
        $name = $params['caption'];
        $children = trim($params['childs']);

        $name = $params[0];
        $caption_ca = $caption_es = $caption_en =$this->key_to_title($name);
        if (isset($params['caption'])) {
            $caption = $params['caption'];
            $caption_ca = $this->key_to_title($caption[0]);
            $caption_es = $this->key_to_title(isset($caption[1]) ? $caption[1] : $caption[0]);
            $caption_en = $this->key_to_title(isset($caption[2]) ? $caption[2] : $caption[0]);
        }


        $name_tag = $this->conn->quote($name);
        $nice_name_ca = $this->conn->quote($caption_ca);
        $nice_name_es = $this->conn->quote($caption_es);
        $nice_name_en = $this->conn->quote($caption_en);



        if (stripos($children, ',') !== false) {// multiple children
            $single_child = "0";
            $multiple_children = $children;
        } else {// single child
            $single_child = $children;
            $multiple_children = '';
        }

        array_push($this->queries, "insert into omp_relations (id, name, caption, language, tag, parent_class_id, child_class_id, multiple_child_class_id, order_type, join_icon, create_icon, join_massive, caption_ca, caption_es, caption_en, autocomplete)
			values($id, $name_tag, '$name', 'ALL', $name_tag, '$parent', '$single_child', '$multiple_children', 'M', 'Y', 'Y', 'N', $nice_name_ca, $nice_name_es, $nice_name_en, 'Y');");
    }

    public function get_relation_name($rel_id, $parent_id, $childs_array)
    {
        $classes_with_url_nice = $this->data['classes_with_url_nice'];
        $other_classes = $this->data['other_classes'];
        $relation_names = $this->data['relation_names'];

        if (array_key_exists($rel_id, $relation_names)) {
            if (is_array($relation_names[$rel_id])) {
                $name[0] = $relation_names[$rel_id][0];
                $name[1] = $relation_names[$rel_id][1];

                if (isset($relation_names[$rel_id][2])) {
                    $name[2] = $relation_names[$rel_id][2];
                }
                if (isset($relation_names[$rel_id][3])) {
                    $name[3] = $relation_names[$rel_id][3];
                }
            } else {
                $name = $relation_names[$rel_id];
            }
        } else {
            if (isset($classes_with_url_nice[$parent_id])) {
                $name = $classes_with_url_nice[$parent_id];
            } else {
                $name = $other_classes[$parent_id];
            }

            if (count($childs_array) > 1) {
                $name .= '_pages';
            } else {
                if (isset($classes_with_url_nice[$childs_array[0]])) {
                    $name .= '_' . $classes_with_url_nice[$childs_array[0]];
                } else {
                    $name .= '_' . $other_classes[$childs_array[0]];
                }
            }
        }
        return $name;
    }

    public function create_class_group($group, $id, $caption_ca = null, $caption_es = null, $caption_en = null)
    {
        if ($caption_ca == null) {
            $caption_ca = $group;
        }
        if ($caption_es == null) {
            $caption_es = $group;
        }
        if ($caption_en == null) {
            $caption_en = $group;
        }

        array_push($this->queries, "insert into omp_class_groups (id, caption, caption_ca, caption_es, caption_en, ordering) values ($id, '$group', '$caption_ca', '$caption_es', '$caption_en', $id);");
    }

    public function create_class($id, $key, $grp_id, $grp_order, $caption = null, $caption_ca = null, $caption_es = null, $caption_en = null, $editableclass='Y')
    {
        $name = $this->key_to_title($key);
        if ($caption == null) {
            $caption = $name;
        }

        /*
        echo "key=$key ";
        $key = $this->title_to_key($key);
        echo "y ahora key=$key\n";
        */

        if (is_array($grp_id)) {
            $grp_id = $grp_id[0];
        }

        if (isset($this->data['bloqued_class']) && !empty($this->data['bloqued_class'])) {
            foreach ($this->data['bloqued_class'] as $bloqued_class) {
                if ($bloqued_class==$id) {
                    $editableclass='N';
                }
            }
        }
        if ($caption_ca==null) {
            $caption_ca=$caption;
        }
        if ($caption_es==null) {
            $caption_es=$caption;
        }
        if ($caption_en==null) {
            $caption_en=$caption;
        }
        array_push($this->queries, "insert into omp_classes (id, name, tag, grp_id, grp_order, name_ca, name_es, name_en, recursive_clone) values ($id, '$key', '$key', $grp_id, $grp_order, '$caption_ca', '$caption_es', '$caption_en', 'N');");
        array_push($this->queries, "insert into omp_roles_classes (class_id, rol_id, browseable, insertable, editable, deleteable, permisos, status1, status2, status3, status4, status5) values ($id, 1, 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y');");
        array_push($this->queries, "insert into omp_roles_classes (class_id, rol_id, browseable, insertable, editable, deleteable, permisos, status1, status2, status3, status4, status5) values ($id, 2, 'Y', '$editableclass', 'Y', '$editableclass', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y');");

        if (!empty($this->data['roles'])) {
            $roles = $this->data['roles'];

            foreach ($roles as $aRole) {
                if (empty($aRole['id']) || in_array($aRole['id'], array(1, 2))) {
                    continue;
                }

                $currentRoleId = $aRole['id'];

                if (empty($aRole['classes'])) {
                    array_push($this->queries, "insert into omp_roles_classes (class_id, rol_id, browseable, insertable, editable, deleteable, permisos, status1, status2, status3, status4, status5) values ($id, $currentRoleId, 'Y', '$editableclass', 'Y', '$editableclass', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y');");
                } else {
                    if (is_array($aRole['classes'])) {
                        foreach ($aRole['classes'] as $role_key => $role_class) {
                            if ($id == $role_key) {
                                $browseableField = isset($role_class['browseable']) ? $role_class['browseable'] : 'Y';
                                $insertableField = isset($role_class['insertable']) ? $role_class['insertable'] : $editableclass;
                                $editableField = isset($role_class['editable']) ? $role_class['editable'] : 'Y';
                                $deleteableField = isset($role_class['deleteable']) ? $role_class['deleteable'] : $editableclass;
                                $permisosField = isset($role_class['permisos']) ? $role_class['permisos'] : 'Y';
                                $status1Field = isset($role_class['status1']) ? $role_class['status1'] : 'Y';
                                $status2Field = isset($role_class['status2']) ? $role_class['status2'] : 'Y';
                                $status3Field = isset($role_class['status3']) ? $role_class['status3'] : 'Y';
                                $status4Field = isset($role_class['status4']) ? $role_class['status4'] : 'Y';
                                $status5Field = isset($role_class['status5']) ? $role_class['status5'] : 'Y';

                                array_push($this->queries, "insert into omp_roles_classes (class_id, rol_id, browseable, insertable, editable, deleteable, permisos, status1, status2, status3, status4, status5) values ($id, $currentRoleId, '$browseableField', '$insertableField', '$editableField', '$deleteableField', '$permisosField', '$status1Field', '$status2Field', '$status3Field', '$status4Field', '$status5Field');");
                            }
                        }
                    } else {
                        $roleClassesId = explode(',', $aRole['classes']);

                        if (in_array($id, $roleClassesId)) {
                            array_push($this->queries, "insert into omp_roles_classes (class_id, rol_id, browseable, insertable, editable, deleteable, permisos, status1, status2, status3, status4, status5) values ($id, $currentRoleId, 'Y', '$editableclass', 'Y', '$editableclass', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y');");
                        }
                    }
                }
            }
        }
    }

    public function create_params_attribute($id, $type, $language_id = 0, $language = 'ALL', $values=null)
    {
        $localized_attributes = $this->data['localized_attributes'];
        $simple_attributes = $this->data['simple_attributes'];

        $key = $values[0];
        $name = $caption_ca = $caption_es = $caption_en =$this->key_to_title($key);
        if (isset($values['caption'])) {
            $caption = $values['caption'];
            $caption_ca = $this->key_to_title($caption[0]);
            $caption_es = $this->key_to_title(isset($caption[1]) ? $caption[1] : $caption[0]);
            $caption_en = $this->key_to_title(isset($caption[2]) ? $caption[2] : $caption[0]);
        }


        $name = $this->conn->quote($name);
        $caption_ca = $this->conn->quote($caption_ca);
        $caption_es = $this->conn->quote($caption_es);
        $caption_en = $this->conn->quote($caption_en);


        $tag = $key = $this->clean_characters($key);
        if ($language != 'ALL') {
            $key = $key . '_' . $language;
            $original_id = $id;
            $id = $id + $language_id;
            if (array_key_exists($id, array_merge($localized_attributes, $simple_attributes, $this->data['original_localized_attributes']))) {
                echo "Attribute $id already exists!!!!";
                print_r($localized_attributes);
                print_r($this->data['original_localized_attributes']);
                print_r($simple_attributes);
                die;
            }
            $this->data['original_localized_attributes'][$original_id] = $id;
            $localized_attributes[$id] = $language_id;
        } else {
            if (array_key_exists($id, $simple_attributes)) {
                echo "Attribute $id already exists!!!!\n";
                print_r($simple_attributes);
                die;
            }
            $simple_attributes[$id] = 0;
        }


        $img_width = $img_height = $lookup_id = 'null';
        if (isset($values['params'])) {
            $params = $values['params'];
            if (isset($params['size'])) {
                $arr_sizes = explode('x', $params['size'][0]);

                if (isset($arr_sizes[0]) && !empty($arr_sizes[0])) {
                    $img_width = $arr_sizes[0];
                }

                if (isset($arr_sizes[1]) && !empty($arr_sizes[1])) {
                    $img_height = $arr_sizes[1];
                }
            }

            $json_params = json_encode($params);
            if (isset($params['lookup'])) {
                $lookup_id = $id;
                array_push($this->queries, "insert into omp_lookups (id, name, type, default_id) values ($lookup_id, $name, 'L', 0);");
                $i = 0;
                foreach ($params['lookup'] as $value_key => $value) {
                    array_push($this->queries, "insert into omp_lookups_values (id, lookup_id, ordre, value, caption_ca, caption_es, caption_en)
                                values ($value_key, $lookup_id, $i, '$value[0]', '$value[1]', '$value[2]', '$value[3]');");
                    if ($i == 0) {
                        array_push($this->queries, "update omp_lookups set default_id='" . $value_key . "' where id=$lookup_id;\n");
                    }
                    $i++;
                }
            }
        } else {
            $json_params = null;
        }

        if (isset($values['description'])) {
            $description = $values['description'];
        } else {
            $description = null;
        }

        array_push($this->queries, "insert into omp_attributes (id, name, caption, tag, type, lookup_id, language,
                                    caption_ca, caption_es, caption_en, img_width, img_height, params, description)
                                    values ($id, '$key', $name, '$tag', '$type', $lookup_id, '$language',
                                    $caption_ca, $caption_es, $caption_en, $img_width, $img_height, '$json_params', '$description');");
    }

    public function create_attribute($id, $key, $type, $language_id = 0, $language = 'ALL', $lookup_id = 0, $caption = null)
    {
        $localized_attributes = $this->data['localized_attributes'];
        $simple_attributes = $this->data['simple_attributes'];
        //$original_localized_attributes = $this->data['original_localized_attributes'];

        if ($caption != null) {
            if (is_array($caption)) {
                $name = $this->key_to_title($caption[0]);
                $caption_ca = $caption[0];
                $caption_es = $this->key_to_title(isset($caption[1]) ? $caption[1] : $caption[0]);
                $caption_en = $this->key_to_title(isset($caption[2]) ? $caption[2] : $caption[0]);
            } else {
                $name = $this->key_to_title($caption);
                $caption_ca = $name;
                $caption_es = $name;
                $caption_en = $name;
            }
        } else {
            $name = $this->key_to_title($key);
            $caption_ca = $name;
            $caption_es = $name;
            $caption_en = $name;
        }

        $name = $this->conn->quote($name);
        $caption_ca = $this->conn->quote($caption_ca);
        $caption_es = $this->conn->quote($caption_es);
        $caption_en = $this->conn->quote($caption_en);

        //echo "create attribute id=$id key=$key type=$type language_id=$language_id language=$language lookup_id=$lookup_id caption_ca=$caption_ca caption_es=$caption_es caption_en=$caption_en\n";

        $tag = $key = $this->clean_characters($key);
        if ($language != 'ALL') {
            $key = $key . '_' . $language;
            $original_id = $id;
            $id = $id + $language_id;
            if (array_key_exists($id, array_merge($localized_attributes, $simple_attributes, $this->data['original_localized_attributes']))) {
                echo "Attribute $id already exists!!!!";
                print_r($localized_attributes);
                print_r($this->data['original_localized_attributes']);
                print_r($simple_attributes);
                die;
            }
            $this->data['original_localized_attributes'][$original_id] = $id;
            $localized_attributes[$id] = $language_id;
        } else {
            if (array_key_exists($id, $simple_attributes)) {
                echo "Attribute $id already exists!!!!\n";
                print_r($simple_attributes);
                die;
            }
            $simple_attributes[$id] = 0;
        }

        if ($lookup_id <= 0) {
            $lookup_id = 'null';
        }

        array_push($this->queries, "insert into omp_attributes (id, name, caption, tag, type, lookup_id, language, caption_ca, caption_es, caption_en)
																	values ($id, '$key', $name, '$tag', '$type', $lookup_id, '$language', $caption_ca, $caption_es, $caption_en);");
    }

    public function create_class_attribute($class_id, $atri_id, $rel_id, $tab_id, $fila, $columna, $is_key, $is_mandatory)
    {
        if ($is_key) {
            $ordre_key = 1;
        } else {
            $ordre_key = 'null';
        }

        if ($is_mandatory) {
            $mandatory = 'Y';
        } else {
            $mandatory = 'N';
        }

        if ($atri_id <= 0) {
            $atri_id = 'null';
        }

        if ($rel_id <= 0) {
            $rel_id = 'null';
        }

        if ($atri_id!='null' || $rel_id!='null') {
            array_push($this->queries, "insert into omp_class_attributes (class_id, atri_id, rel_id, tab_id, fila, columna, caption_position, ordre_key, mandatory, detail)
            values ($class_id, $atri_id, $rel_id, $tab_id, $fila, $columna, 'left', $ordre_key, '$mandatory', 'N');");
        }
    }

    public function create_tab($key, $val, $order)
    {
        array_push($this->queries, "insert into omp_tabs (id, name, name_ca, name_es, name_en, ordering) values ($key, '$val', '$val', '$val', '$val', $order);");
    }

    public function key_to_title($key)
    {
        $str = str_replace('_', ' ', $key);
        $str = str_replace('-', ' ', $str);
        $str = ucwords($str);
        return $str;
    }

    public function title_to_key($key)
    {
        $str = $this->clean_characters($key);
        $str = ucwords($str);
        $str = str_replace(' ', '', $str);
        return $str;
    }

    public function clean_characters($key)
    {
        $str = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $key
        );

        $str = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $str
        );

        $str = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $str
        );

        $str = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $str
        );

        $str = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $str
        );

        $str = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç'),
            array('n', 'N', 'c', 'C'),
            $str
        );
        return $str;
    }

    // Data
    private function editoraDefaultData()
    {
        return array(
            'attributes_params' => false,
            'nomintern_id' => $this->editoraDefaultNomInternId(),
            'nomintern_name' => $this->editoraDefaultNomInternName(),
            'nomintern_caption' => $this->editoraDefaultNomInternCaption(),
            'niceurl_id' => 2,
            'niceurl_name' => 'niceurl',
            'users' => array(),
            'languages' => array(),
            'groups' => array(),
            'classes' => array(),
            'attributes_order_date' => array(),
            'attributes_order_string' => array(),
            'attributes_string' => array(),
            'attributes_color' => array(),
            'attributes_textarea' => array(),
            'attributes_text' => array(),
            'attributes_multi_lang_text' => array(),
            'attributes_date' => array(),
            'attributes_num' => array(),
            'attributes_geolocation' => array(),
            'attributes_url' => array(),
            'attributes_multi_lang_url' => array(),
            'attributes_file' => array(),
            'attributes_privatefile' => array(),
            'attributes_video' => array(),
            'attributes_lookup' => array(),
            'attributes_image' => array(),
            'attributes_grid_image' => array(),
            'images_sizes' => array(),
            'attributes_multi_lang_string' => array(),
            'attributes_multi_lang_textarea' => array(),
            'attributes_multi_lang_file' => array(),
            'attributes_multi_lang_privatefile' => array(),
            'attributes_multi_lang_image' => array(),
            'attributes_multi_lang_video' => array(),

            'attributes_json' => array(),
            'attributes_multi_lang_json' => array(),

            'lookups' => array(),
            'relations' => array(),
            'relation_names' => array(),
            'attributes_classes' => array(),
            'roles' => $this->editoraDefaultRoles(),
            'tabs' => array(
                1 => 'data',
                100 => 'seo' ,
            )
        );
    }
}
