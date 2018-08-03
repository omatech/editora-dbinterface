<?php

namespace Omatech\Editora\FakeContent;
use \Omatech\Editora\DBInterfaceBase;
use \Doctrine\DBAL\DriverManager;
use Omatech\Editora\Loader\Loader;

class FakeContent extends DBInterfaceBase
{
    public $file_base = '';
    public $url_base = '';
    public $geocoder;

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

        $classes = DBInterfaceBase::getAllClass();

        foreach($classes as $key=>$class){

            //No lo aplica para: Global, Home.
            if($class['class_id'] != 1 && $class['class_id'] != 10){

                $attributes = DBInterfaceBase::getAllAttributesInClass($class['class_id']);
                $attributes_values = [];

                //Number of elements to create.
                for($i=1; $i<4; $i++){

                    $nom_intern = $class['name'].'_FAKE';
                    $inst_id = $loader->insertInstanceWithExternalID($class['class_id'], $nom_intern, $external_id, $batch_id, []);

                    foreach($attributes as $key1=>$attribute){

                        switch($attribute['type']){

                            case 'Z':
                                $niceurl = $attribute['name'] .'_'.$inst_id;
                                $attributes_values[$attribute['name']] = $niceurl;
                                $loader->insertUrlNice($niceurl, $inst_id, $attribute['language']);
                                break;

                            case 'S':
                                //Faker

                                $attributes_values[$attribute['name']] = $attribute['name'];
                                //$attributes_values[$attribute['name']] = $faker->text;
                                break;

                            case 'A':
                            case 'T':
                                $attributes_values[$attribute['name']] = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean aliquet ac nisi sed aliquam. Phasellus ac lacinia lacus. Phasellus ornare sem sit amet erat vehicula consectetur. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec maximus elit in sem tincidunt, at aliquam nunc pretium. Mauris a massa consectetur, mattis purus eu, ultricies nisl. Vivamus tincidunt risus a pretium commodo. Nam id nisi velit. Sed eu purus vitae diam porttitor pretium ut eu diam. Phasellus pharetra non urna at vestibulum.';
                                break;

                            case 'K':
                                $attributes_values[$attribute['name']] = '<b>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</b> Aenean aliquet ac nisi sed aliquam. Phasellus ac lacinia lacus. Phasellus ornare sem sit amet erat vehicula consectetur. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec maximus elit in sem tincidunt, at aliquam nunc pretium. Mauris a massa consectetur, mattis purus eu, ultricies nisl. Vivamus tincidunt risus a pretium commodo. Nam id nisi velit. Sed eu purus vitae diam porttitor pretium ut eu diam. Phasellus pharetra non urna at vestibulum.';
                                break;

                            case 'U':
                                $attributes_values[$attribute['name']] = 'http://www.omatech.com';
                                break;

                            case 'I':
                                if(empty($attribute['img_width']) && empty($attribute['img_height'])){
                                    $width = '600';
                                    $height = '600';
                                }else {
                                    if (empty($attribute['img_width'])) {
                                        $width = $attribute['img_height'];
                                        $height = $attribute['img_height'];
                                    }elseif(empty($attribute['img_height'])){
                                        $width = $attribute['img_width'];
                                        $height = $attribute['img_width'];
                                    }else{
                                        $width = $attribute['img_width'];
                                        $height = $attribute['img_height'];
                                    }
                                }
                                //$attributes_values[$attribute['name']] = 'http://lorempixel.com/'.$width.'/'.$height.'/nature/';
                                $attributes_values[$attribute['name']] = 'https://www.dummyimage.com/'.$width.'x'.$height.'/000/00ffd5.png';
                                break;

                            case 'Y':
                                $attributes_values[$attribute['name']] = 'youtube:GnSmcHet1eM';
                                break;

                            case 'F':
                                //Change function as in image I.
                                $attributes_values[$attribute['name']] = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';
                                break;

                            //Dates
                        }

                    }

                    $attributes_values['nom_intern'] = $nom_intern.'_'.$inst_id;
                    $loader->updateInstance($inst_id, $attributes_values['nom_intern'], $attributes_values );
                    echo('.');
                }
            }
        }
    }
}