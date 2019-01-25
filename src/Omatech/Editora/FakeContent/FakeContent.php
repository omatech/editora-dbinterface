<?php

namespace Omatech\Editora\FakeContent;
use \Omatech\Editora\DBInterfaceBase;
use \Doctrine\DBAL\DriverManager;
use Omatech\Editora\Loader\Loader;

use Faker\Factory as Faker;

class FakeContent extends DBInterfaceBase
{
    public $file_base = '';
    public $url_base = '';
    public $geocoder;
		public $num_instances=4;
		public $include_classes='';
		public $exclude_classes='';
		public $pictures_theme='cats';

    public function __construct($conn, $params=array(), $geocoder = null) {

        parent::__construct($conn, $params);
        $this->geocoder = $geocoder;
    }

    /**
     * @param $data
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function createContentEditora($conn)
    {
        $loader=new Loader($conn, array('download_images'=>false));
        $external_id = -1;
        $batch_id = time();
				$attribute_count=0;
				$relation_instance_count=0;
				$instance_count=0;

        $classes = DBInterfaceBase::getAllClasses($this->include_classes, $this->exclude_classes);
        $faker = Faker::create();

        //Clases
        foreach($classes as $key=>$class) {

            //No lo aplica para: Global, Home.
            if ($class['class_id'] != 1 && $class['class_id'] != 10) {

                $attributes = DBInterfaceBase::getAllAttributesInClass($class['class_id']);
                $attributes_values = [];

                //Number of elements to create.
                for ($i = 0; $i <= $this->num_instances; $i++) {

                    $nom_intern = $class['name'] . '_FAKE';
                    $inst_id = $loader->insertInstanceWithExternalID($class['class_id'], $nom_intern, $external_id, $batch_id, []);

                    foreach ($attributes as $key1 => $attribute) {

                        switch ($attribute['type']) {

                            case 'A': /* Text Area WYSIWYG */
                                $attributes_values[$attribute['name']] = $faker->sentence(rand(50, 300), true);
                                break;

                            case 'B': /* String d'una linea ordenable a l'extracció */
                                //$attributes_values[$attribute['name']] = $faker->sentence(rand(1, 3), true);
                                break;

                            case "C": /* Text Area Code */
                                break;

                            case "D": /* Date */
                                break;

                            case "E": /* Date ordenable a l'extracció */
                                break;

                            case 'F': /* File */
                                //Change function as in image I for download.
                                $attributes_values[$attribute['name']] = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';
                                break;

                            case "G": /* Flash File */
                                break;

                            case 'H': /* Grid Imatge */
                                if (empty($attribute['img_width']) && empty($attribute['img_height'])) {
                                    $width = '600';
                                    $height = '600';
                                } else {
                                    if (empty($attribute['img_width'])) {
                                        $width = $attribute['img_height'];
                                        $height = $attribute['img_height'];
                                    } elseif (empty($attribute['img_height'])) {
                                        $width = $attribute['img_width'];
                                        $height = $attribute['img_width'];
                                    } else {
                                        $width = $attribute['img_width'];
                                        $height = $attribute['img_height'];
                                    }
                                }
                                //$attributes_values[$attribute['name']] = 'http://lorempixel.com/'.$width.'/'.$height.'/nature/';
                                $attributes_values[$attribute['name']] = 'https://www.dummyimage.com/'.$width.'x'.$height.'/000/00ffd5.png';
                                //$attributes_values[$attribute['name']] = $faker->imageUrl($width, $height, $this->pictures_theme, true, 'Omatech');
                                break;

                            case 'I': /* Imatge */
                                if (empty($attribute['img_width']) && empty($attribute['img_height'])) {
                                    $width = '600';
                                    $height = '600';
                                } else {
                                    if (empty($attribute['img_width'])) {
                                        $width = $attribute['img_height'];
                                        $height = $attribute['img_height'];
                                    } elseif (empty($attribute['img_height'])) {
                                        $width = $attribute['img_width'];
                                        $height = $attribute['img_width'];
                                    } else {
                                        $width = $attribute['img_width'];
                                        $height = $attribute['img_height'];
                                    }
                                }
                                //$attributes_values[$attribute['name']] = 'http://lorempixel.com/'.$width.'/'.$height.'/nature/';
                                $attributes_values[$attribute['name']] = 'https://www.dummyimage.com/'.$width.'x'.$height.'/000/00ffd5.png';
                                //$attributes_values[$attribute['name']] = $faker->imageUrl($width, $height, $this->pictures_theme, true, 'Omatech');
                                break;

                            case 'K': /* Text Area CKEDITOR */
                                $attributes_values[$attribute['name']] = '<b>' . $faker->sentence(rand(1, 15), true) . '</b>' . $faker->sentence(rand(50, 300), true);
                                break;

                            //case "L": /* Lookup */
                                //break;

                            case "M": /* Geoposicionament amb google Maps */
                                break;

                            case "N": /* Numeric */
                                break;

                            case "O":/* Selector color */
                                break;

                            //case "R": /* Relation */
                                //break;

                            case 'S': /* String d'una linea */
                                //$attributes_values[$attribute['name']] = $attribute['name'];
                                $attributes_values[$attribute['name']] = $faker->sentence(rand(1, 3), true);
                                break;

                            case 'T': /* Text Area HTML */
                                $attributes_values[$attribute['name']] = $faker->sentence(rand(100, 500), true);
                                break;

                            case 'U':  /* URL */
                                $attributes_values[$attribute['name']] = 'http://www.omatech.com';
                                break;

                            case "W": /* Type APP */
                                break;

                            case "X": /* XML */
                                break;

                            case 'Y': /* Video Youtube - Vimeo */
                                $attributes_values[$attribute['name']] = 'youtube:GnSmcHet1eM';
                                break;

                            case 'Z': /* niceurl */
                                $niceurl = $attribute['name'] . '_' . $inst_id;
                                $attributes_values[$attribute['name']] = $niceurl;
                                $loader->insertUrlNice($niceurl, $inst_id, $attribute['language']);
                                break;

                            //Dates
                            //Map latitude(min,max), longitude(min,max)
                        }
												
                    }

                    $attributes_values['nom_intern'] = $nom_intern . '_' . $inst_id;
                    $loader->updateInstance($inst_id, $attributes_values['nom_intern'], $attributes_values);
										$attribute_count+=count($attributes_values);
										$instance_count++;
                    echo('i');
                }
            }
        }

        //Relaciones
        foreach($classes as $key=>$class){

            //No lo aplica para: Global, Home.
            if($class['class_id'] != 1 && $class['class_id'] != 10) {

                $relations = DBInterfaceBase::getClassRelations($class['class_id']);
                if(isset($relations)) {

                    foreach ($relations as $relation) {

                        $instances_class = $loader->getAllInstancesClassId($relation['parent_class_id'], $batch_id);

                        foreach ($instances_class as $key_instance_class => $instance_class) {

                            $instances_rel = [];
                            if (strcmp($relation['child_class_id'], '0') == 0) {
                                $classes_rel = explode(",", $relation['multiple_child_class_id']);
                                foreach ($classes_rel as $key_rel => $class_rel) {
                                    $instances_rel[$key_rel] = DBInterfaceBase::getInstanceRandomClassID($class_rel);
                                }
                            } else {
                                $instances_rel[0] = DBInterfaceBase::getInstanceRandomClassID($relation['child_class_id']);
                            }

                            foreach ($instances_rel as $instance_rel) {
                                $result = $loader->insertRelationInstance($relation['id'], $instance_class['id'], $instance_rel['id'], -1, $batch_id);
																$relation_instance_count++;
                            }
                            echo('r');
                        }
                    }
                }
            }
        }
				echo "\nContent created: $instance_count instances $attribute_count attributes and $relation_instance_count relation instances created with batch_id=$batch_id\n";
    }


}