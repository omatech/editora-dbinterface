<?php
/**
 * Created by Omatech
 * Date: 26/04/18 12:24
 */

namespace Omatech\Editora\Generator;
use \Omatech\Editora\DBInterfaceBase;

class Generator extends DBInterfaceBase
{
    protected $data;
    protected $queries;

    public function __construct($conn, $params)
    {
        parent::__construct($conn, $params);
    }

    /**
     * @param $data
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function createEditora($data)
    {

        $this->data = $this->editoraPrepareData($data);
        $this->queries = array();

        extract(
            $this->data,
            EXTR_OVERWRITE
        );

        $this->create_attribute($nomintern_id, $nomintern_name, 'S');


        $i=2;
        foreach ($languages as $key_lang=>$val_lang)
        {
            $this->create_attribute($niceurl_id, $niceurl_name, 'Z', $key_lang, $val_lang);
            $this->create_tab($key_lang, $val_lang, $i++);
        }

        if(isset($roles) && is_array($roles))
        {
            foreach ($roles as $aRole){

                if(!isset($aRole['id']) || !isset($aRole['name'])){
                    //TODO throw
                }

                array_push($this->queries, 'INSERT INTO `omp_roles` VALUES (\''.$aRole['id'].'\', \''.$aRole['name'].'\', \'Y\');');
            }
        }else{
            //TODO throw
        }

        if (isset($groups) && isset($classes))
        {// new method
            array_push($this->queries, "delete from omp_class_groups;");
            $i=1;
            foreach ($groups as $key=>$val)
            {
                $this->create_class_group($key, $i++);
            }

            $i=1;
            foreach ($classes as $group_key=>$group_val)
            {
                foreach ($group_val as $key=>$val)
                {
                    if(isset($classes_caption)){
                        if(array_key_exists($key,$classes_caption)){
                            $caption = $classes_caption[$key];
                        }else{
                            $caption=$this->key_to_title($val);
                        }
                    }else{
                        $caption=$this->key_to_title($val);
                    }

                    $this->create_class($key, $val, $groups[$group_key], $i++, $caption);
                    $this->create_class_attribute($key, $nomintern_id, 0, 1, 1, 1, true, true);
                    $need_url_nice=$groups[$group_key];
                    /*
                    if ($need_url_nice==1)
                    {// Group amb url nice
                            foreach ($languages as $key_lang=>$val_lang)
                            {
                                create_class_attribute($key, $key_lang+$niceurl_id, 0, $key_lang, 1, 2, false, false);
                            }
                    }
                    */
                }
            }
        }
        else
        {// old method
            $i=0;
            foreach ($classes_with_url_nice as $key=>$val)
            {
                $this->create_class($key, $val, 1, $i++);
                $this->create_class_attribute($key, $nomintern_id, 0, 1, 1, 1, true, true);
                foreach ($languages as $key_lang=>$val_lang)
                {
                    $this->create_class_attribute($key, $key_lang+$niceurl_id, 0, $key_lang, 1, 2, false, false);
                }
            }

            $i=0;
            foreach ($other_classes as $key=>$val)
            {
                $this->create_class_attribute($key, $nomintern_id, 0, 1, 1, 1, true, true);
                $this->create_class($key, $val, 2, $i++);
            }

        }

        foreach ($users as $user)
        {
            array_push($this->queries, "insert into omp_users (username, password, complete_name, rol_id, language, tipus) values ('$user[0]', '$user[1]', '$user[2]', 2, '$user[3]', 'U');");
        }

        foreach ($lookups as $lookup_key=>$lookup)
        {
            $arr_lookup_info=explode(',',$lookup_key);
            $lookup_id=$arr_lookup_info[0];
            $lookup_name=$arr_lookup_info[1];
            array_push($this->queries, "insert into omp_lookups (id, name, type, default_id) values ($lookup_id, '$lookup_name', 'L', 0);");
            $i=0;
            foreach ($lookup as $value_key=>$value)
            {
                array_push($this->queries, "insert into omp_lookups_values (id, lookup_id, ordre, value, caption_es, caption_en, caption_ca) values ($value_key, $lookup_id, $i, '$value[0]', '$value[1]', '$value[3]', '$value[3]');");
                if ($i==0)
                {
                    array_push($this->queries, "update omp_lookups set default_id='".$value_key."' where id=$lookup_id;\n");
                }
                $i++;
            }
        }

        foreach ($attributes_multi_lang_string as $key=>$val)
        {
            foreach ($languages as $key_lang=>$val_lang)
            {
                $this->create_attribute($key, $val, 'S', $key_lang, $val_lang);
            }
        }

        foreach ($attributes_multi_lang_textarea as $key=>$val)
        {
            foreach ($languages as $key_lang=>$val_lang)
            {
                $this->create_attribute($key, $val, 'K', $key_lang, $val_lang);
            }
        }

        foreach ($attributes_textarea as $key=>$val)
        {
            $this->create_attribute($key, $val, 'K');
        }

        foreach ($attributes_text as $key=>$val)
        {
            $this->create_attribute($key, $val, 'T');
        }

        foreach ($attributes_multi_lang_image as $key=>$val)
        {
            foreach ($languages as $key_lang=>$val_lang)
            {
                $this->create_attribute($key, $val, 'I', $key_lang, $val_lang);
            }
        }

        foreach ($attributes_multi_lang_file as $key=>$val)
        {
            foreach ($languages as $key_lang=>$val_lang)
            {
                $this->create_attribute($key, $val, 'F', $key_lang, $val_lang);
            }
        }

        if(isset($attributes_multi_lang_url)) {
            foreach ($attributes_multi_lang_url as $key => $val) {
                foreach ($languages as $key_lang => $val_lang) {
                    $this->create_attribute($key, $val, 'U', $key_lang, $val_lang);
                }
            }
        }

        if(isset($attributes_multi_lang_video)) {
            foreach ($attributes_multi_lang_video as $key => $val) {
                foreach ($languages as $key_lang => $val_lang) {
                    $this->create_attribute($key, $val, 'Y', $key_lang, $val_lang);
                }
            }
        }

        foreach ($attributes_file as $key=>$val)
        {
            $this->create_attribute($key, $val, 'F');
        }


        foreach ($attributes_string as $key=>$val)
        {
            if(is_array($val)){
                $caption = $val[1];
                $this->create_attribute($key, $val[0], 'S', 0, 'ALL', 0, $caption);
            }else{
                $this->create_attribute($key, $val, 'S');
            }
        }

        foreach ($attributes_image as $key=>$val)
        {
            $this->create_attribute($key, $val, 'I');
        }

        foreach ($attributes_geolocation as $key=>$val)
        {
            $this->create_attribute($key, $val, 'M');
        }

        foreach ($attributes_date as $key=>$val)
        {
            if(is_array($val)){
                $caption = $val[1];
                $this->create_attribute($key, $val[0], 'D', 0, 'ALL', 0, $caption);

            }else{
                $this->create_attribute($key, $val, 'D');
            }
        }

        foreach ($attributes_num as $key=>$val)
        {
            $this->create_attribute($key, $val, 'N');
        }

        foreach ($attributes_video as $key=>$val)
        {
            $this->create_attribute($key, $val, 'Y');
        }

        foreach ($attributes_url as $key=>$val)
        {
            $this->create_attribute($key, $val, 'U');
        }

        foreach ($attributes_lookup as $key=>$val)
        {
            $arr_val=explode(',',$val);
            $lookup_name=$arr_val[0];
            $lookup_id=$arr_val[1];
            $this->create_attribute($key, $lookup_name, 'L', 0, 'ALL', $lookup_id);
        }

        foreach ($attributes_classes as $key=>$val)
        {
            $filas=[1=>2];
            foreach ($languages as $key_lang=>$val_lang)
            {
                $filas[$key_lang]=2;
            }

            $attributes_in_class=explode(',', $val);
            foreach ($attributes_in_class as $atri_id)
            {

                $atri_ids = explode('-', $atri_id);
                $atri_id = $atri_ids[0];

                if (stripos($atri_id, '*')!==false){
                    $atri_id = str_replace('*', '', $atri_id);
                    $mandatory = true;
                }else{
                    $mandatory = false;
                }

                if (array_key_exists($atri_id, $original_localized_attributes))
                {// es un atribut localized
                    foreach ($languages as $key_lang=>$val_lang)
                    {
                        $this->create_class_attribute($key, $atri_id+$key_lang, 0, $key_lang, $filas[$key_lang], 1, false, $mandatory);
                        $filas[$key_lang]=$filas[$key_lang]+1;
                    }
                }
                else
                {
                    $this->create_class_attribute($key, $atri_id, 0, 1, $filas[1], 1, false, $mandatory);
                    $filas[1]=$filas[1]+1;
                }

                if (array_key_exists(1,$atri_ids)){
                    $atri_id = $atri_ids[1];
                    if (stripos($atri_id, '*')!==false){
                        $atri_id = str_replace('*', '', $atri_id);
                        $mandatory = true;
                    }else{
                        $mandatory = false;
                    }

                    if (array_key_exists($atri_id, $original_localized_attributes))
                    {// es un atribut localized
                        foreach ($languages as $key_lang=>$val_lang)
                        {
                            $this->create_class_attribute($key, $atri_id+$key_lang, 0, $key_lang, $filas[$key_lang]-1, 2, false, $mandatory);
                        }
                    }
                    else
                    {
                        $this->create_class_attribute($key, $atri_id, 0, 1, $filas[1]-1, 2, false, $mandatory);
                    }
                }
            }
            $global_filas[$key]=$filas;
        }

        foreach ($relations as $key=>$val)
        {
            $arr_ids=explode(',', $val);
            $parent=array_shift($arr_ids);
            $name=$this->get_relation_name($key, $parent, $arr_ids);
            $childs=implode(',', $arr_ids);
            $this->create_relation($key, $parent, $childs, $name);
            if (isset($global_filas[$parent]))
            {// per si no hem creat tots els class attributes encara

                if (!isset($global_columnas[$parent][1]) ||  $global_columnas[$parent][1]==1)
                {// no tenemos la columna previamente (caso inicial) o era 1
                    $this->create_class_attribute($parent, 0, $key, 1, $global_filas[$parent][1], 1, false, false);
                    $global_columnas[$parent][1]=2;
                }
                else
                {// teniamos columna previamente y era la columna2, reseteamos
                    $this->create_class_attribute($parent, 0, $key, 1, $global_filas[$parent][1]++, 2, false, false);
                    $global_columnas[$parent][1]=1;
                }
            }
        }

        foreach ($images_sizes as $key_size=>$size)
        {
            $arr_sizes=explode('x', $size);
            if (isset($arr_sizes[0]) && !empty($arr_sizes[0]))
            {
                array_push($this->queries, "update omp_attributes set img_width=".$arr_sizes[0]." where id=$key_size;");
            }

            if (isset($arr_sizes[1]) && !empty($arr_sizes[1]))
            {
                array_push($this->queries, "update omp_attributes set img_height=".$arr_sizes[1]." where id=$key_size;");
            }
        }

        $this->startTransaction();

        foreach($this->queries as $aQuery)
        {
            try{
                $this->conn->executeQuery($aQuery);
            }catch (\Exception $exception){
                $this->rollback();
                return false;
            }
        }

        $this->commit();

        return true;
    }

    //Default Data

    private function editoraDefaultData(){

        return array(
            'nomintern_id' => $this->editoraDefaultNomInternId(),
            'nomintern_name' => $this->editoraDefaultNomInternName(),
            'niceurl_id' => 2,
            'niceurl_name' => 'niceurl',
            'localized_attributes' => array(),
            'simple_attributes' => array(),
            'original_localized_attributes' => array(),
            'global_filas' => array(),
            'classes_with_url_nice' => array(),
            'users' => array(
                array('user', defaultUserPassword, 'Administrator', 'en')
            ),
            'languages' => array(),
            'groups' => array(),
            'classes' => array(),
            'classes_caption' => array(),
            'attributes_string' => array(),
            'attributes_multi_lang_string' => array(),
            'attributes_multi_lang_textarea' => array(),
            'attributes_textarea' => array(),
            'attributes_text' => array(),
            'attributes_multi_lang_image' => array(),
            'attributes_image' => array(),
            'images_sizes' => array(),
            'attributes_multi_lang_file' => array(),
            'attributes_date' => array(),
            'attributes_num' => array(),
            'attributes_geolocation' => array(),
            'attributes_url' => array(),
            'attributes_multi_lang_url' => array(),
            'attributes_file' => array(),
            'attributes_video' => array(),
            'attributes_lookup' => array(),
            'lookups' => array(),
            'relations' => array(),
            'relation_names' => array(),
            'attributes_classes' => array(),
            'roles' => $this->editoraDefaultRoles(),
            'other_classes' => array(),
            'tabs' => array(
                1 => 'data'
            )
        );


        /*

INSERT INTO `omp_attributes` VALUES ('1', 'nom_intern', 'Nom Intern', 'Nom Intern', 'nom_intern', 'S', null, null, null, '0', null, null, 'ALL', 'Nom Intern', 'Nombre Interno', 'Internal Name');
INSERT INTO `omp_tabs` VALUES ('1', 'dades', 'Dades', 'Datos', 'Data', '1');
INSERT INTO `omp_users` VALUES ('1', 'admin', 'password', 'Omatech', '1', 'ca', 'O'); //Todo pensa

         *
         * */

    }

    public function editoraDefaultNomInternId()
    {
        return 1;
    }

    public function editoraDefaultNomInternName()
    {
        return 'nom_intern';
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

        foreach ($defaultData as $aDefaultDataKey => $aDefaultDataValue)
        {
            if(empty($aDefaultDataValue))
                continue;

            if(isset($data[$aDefaultDataKey]))
            {
                if(!is_array($aDefaultDataValue))
                {
                    $data[$aDefaultDataKey] = $aDefaultDataValue;
                }else{
                    $data[$aDefaultDataKey] = array_merge($aDefaultDataValue, $data[$aDefaultDataKey]);

                }

                unset($defaultData[$aDefaultDataKey]);
            }
        }
        return array_merge($defaultData, $data);
    }


    // funcions auxiliars

    function create_relation ($id, $parent, $children, $name)
    {
        $children = trim($children);
        if(is_array($name)){
            $nice_name = $name[0];
            $name_tag = $name[1];
        }else{
            $nice_name=  $this->key_to_title($name);
            $name_tag = $name;
        }

        if (stripos($children, ',')!==false)
        {// multiple children
            $single_child="0";
            $multiple_children=$children;
        }
        else
        {// single child
            $single_child=$children;
            $multiple_children='';
        }

        array_push($this->queries, "insert into omp_relations (id, name, caption, language, tag, parent_class_id, child_class_id, multiple_child_class_id, order_type, join_icon, create_icon, join_massive, caption_ca, caption_es, caption_en, autocomplete) 
			values($id, '$name_tag', '$nice_name', 'ALL', '$name_tag', $parent, $single_child, '$multiple_children', 'M', 'Y', 'Y', 'N', '$nice_name', '$nice_name', '$nice_name', 'Y');");
    }

    function get_relation_name($rel_id, $parent_id, $childs_array)
    {
        $classes_with_url_nice = $this->data['classes_with_url_nice'];
        $other_classes = $this->data['other_classes'];
        $relation_names = $this->data['relation_names'];

        if (array_key_exists($rel_id, $relation_names))
        {

            if( is_array($relation_names[$rel_id]) )
            {
                $name[0]=$relation_names[$rel_id][0];
                $name[1]=$relation_names[$rel_id][1];
            }else{
                $name=$relation_names[$rel_id];
            }

        }
        else
        {
            if (isset($classes_with_url_nice[$parent_id]))
            {
                $name=$classes_with_url_nice[$parent_id];
            }
            else
            {
                $name=$other_classes[$parent_id];
            }

            if (count($childs_array)>1)
            {
                $name.='_pages';
            }
            else
            {
                if (isset($classes_with_url_nice[$childs_array[0]]))
                {
                    $name.='_'.$classes_with_url_nice[$childs_array[0]];
                }
                else
                {
                    $name.='_'.$other_classes[$childs_array[0]];
                }
            }
        }
        return $name;
    }

    function create_class_group ($group, $id)
    {
        array_push($this->queries, "insert into omp_class_groups (id, caption, caption_ca, caption_es, caption_en, ordering) values ($id, '$group', '$group', '$group', '$group', $id);");
    }

    function create_class($id, $key, $grp_id, $grp_order, $caption = null)
    {
        $name=$this->key_to_title($key);
        if($caption == null){
            $caption = $name;
        }
        $key = $this->title_to_key($key);

        array_push($this->queries, "insert into omp_classes (id, name, tag, grp_id, grp_order, name_ca, name_es, name_en, recursive_clone) values ($id, '$key', '$key', $grp_id, $grp_order, '$caption', '$caption', '$caption', 'N');");
        array_push($this->queries, "insert into omp_roles_classes (class_id, rol_id, browseable, insertable, editable, deleteable, permisos, status1, status2, status3, status4, status5) values ($id, 1, 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y');");
        array_push($this->queries, "insert into omp_roles_classes (class_id, rol_id, browseable, insertable, editable, deleteable, permisos, status1, status2, status3, status4, status5) values ($id, 2, 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y');");

        if(!empty($this->data['roles']))
        {
            $roles = $this->data['roles'];

            foreach ($roles as $aRole)
            {
                if(empty($aRole['id']) || in_array($aRole['id'], array(1, 2)) || empty($aRole['classes'])){
                    continue;
                }

                $roleClassesId = explode(',', $aRole['classes']);

                if(empty($roleClassesId))
                    continue;

                $currentRoleId = $aRole['id'];

                if(in_array($id, $roleClassesId))
                {
                    array_push($this->queries, "insert into omp_roles_classes (class_id, rol_id, browseable, insertable, editable, deleteable, permisos, status1, status2, status3, status4, status5) values ($id, $currentRoleId, 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y');");
                }
            }
        }

    }

    function create_attribute($id, $key, $type, $language_id=0, $language='ALL', $lookup_id=0, $caption=null)
    {
        $localized_attributes = $this->data['localized_attributes'];
        $simple_attributes = $this->data['simple_attributes'];
        $original_localized_attributes = $this->data['original_localized_attributes'];

        if($caption != null){
            $name = $this->key_to_title($caption);
        }else{
            $name=$this->key_to_title($key);
        }

        $tag= $key = $this->clean_characters($key);
        if ($language!='ALL')
        {
            $key=$key.'_'.$language;
            $original_id=$id;
            $id=$id+$language_id;
            if (array_key_exists($id, array_merge($localized_attributes, $simple_attributes, $original_localized_attributes)))
            {
                echo "Attribute $id already exists!!!!";
                print_r($localized_attributes);
                print_r($original_localized_attributes);
                print_r($simple_attributes);
                die;
            }
            $original_localized_attributes[$original_id]=$id;
            $localized_attributes[$id]=$language_id;
        }
        else
        {
            if (array_key_exists($id, $simple_attributes ))
            {
                echo "Attribute $id already exists!!!!\n";
                print_r($simple_attributes);
                die;
            }
            $simple_attributes[$id]=0;
        }

        if ($lookup_id<=0)
        {
            $lookup_id='null';
        }

        array_push($this->queries, "insert into omp_attributes (id, name, caption, tag, type, lookup_id, language, caption_ca, caption_es, caption_en) values ($id, '$key', '$name', '$tag', '$type', $lookup_id, '$language', '$name', '$name', '$name');");
    }

    function create_class_attribute($class_id, $atri_id, $rel_id, $tab_id, $fila, $columna, $is_key, $is_mandatory)
    {
        if ($is_key)
        {
            $ordre_key=1;
        }
        else
        {
            $ordre_key='null';
        }

        if ($is_mandatory)
        {
            $mandatory='Y';
        }
        else
        {
            $mandatory='N';
        }

        if ($atri_id<=0)
        {
            $atri_id='null';
        }

        if ($rel_id<=0)
        {
            $rel_id='null';
        }

        array_push($this->queries,"insert into omp_class_attributes (class_id, atri_id, rel_id, tab_id, fila, columna, caption_position, ordre_key, mandatory, detail) values ($class_id, $atri_id, $rel_id, $tab_id, $fila, $columna, 'left', $ordre_key, '$mandatory', 'N');");
    }

    function create_tab($key, $val, $order)
    {
        array_push($this->queries, "insert into omp_tabs (id, name, name_ca, name_es, name_en, ordering) values ($key, '$val', '$val', '$val', '$val', $order);");
    }

    function key_to_title ($key)
    {
        $str=str_replace('_',' ', $key);
        $str=str_replace('-', ' ', $str);
        $str=ucwords($str);
        return $str;
    }

    function title_to_key ($key)
    {

        $str = $this->clean_characters($key);
        $str = ucwords($str);
        $str = str_replace(' ', '', $str);
        return $str;
    }

    function clean_characters($key){
        $str = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $key
        );

        $str = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $str );

        $str = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $str );

        $str = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $str );

        $str = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $str );

        $str = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç'),
            array('n', 'N', 'c', 'C'),
            $str
        );
        return $str;
    }
}