<?php
/**
 * Created by Omatech
 * User: aroca@omatech.com
 * Date: 26/04/18 12:53
 */

require_once __DIR__ . '/../TestCaseBase.php';

use Omatech\Editora\Generator\Generator;

class GeneratorTest extends TestCaseBase
{
    public function testGenerateEditoraSuccessfully()
    {
        $data = array(
            'nomintern_id' => 1,
            'nomintern_name' => 'test_editora',
            'niceurl_id' => 2,
            'niceurl_name' => 'test-editora',
            'localized_attributes' => array(),
            'simple_attributes' => array(),
            'original_localized_attributes' => array(),
            'global_filas' => array(),
            'users' => array(
                array('test_editora', 'testeditorapassword'.rand(), 'Administrator', 'en')
            ),
            'languages' => array(
                10000=>'es',
                20000=>'en'
            ),
            'groups' => array(
                'Main'=>1,
                'Secondary'=>2,
            ),
            'classes' => array(
                'Main'=> array(
                    10=>'Home'
                ),
                'Secondary'=> array(
                    30=>'case_studies_categories',
                    20=>'case_studies',
                    40=>'job_offers',
                    50=>'people_section',
                    60=>'person'
                )
            ),
            'classes_caption' => array(
                10=>'Home',
                20=>'case_studies_categories',
                30=>'case_studies',
                40=>'job_offers',
                50=>'people_section',
                60=>'person',
            ),
            'attributes_string' => array(
                100=>array('title_unique', 'title_unique'),
                101=>array('kpi_num_1','kpi_num_1'),
                102=>array('kpi_num_2', 'kpi_num_2'),
                103=>array('kpi_num_3', 'kpi_num_3'),
                104=>array('kpi_num_4', 'kpi_num_4'),
                105=>array('city','city')
            ),
            'attributes_multi_lang_string' => array(
                200=>'title',
                201=>'intro',
                202=>'date',
                203=>'kpi_text_1',
                204=>'kpi_text_2',
                205=>'kpi_text_3',
                206=>'kpi_text_4',
                207=>'black_pre_title',
                208=>'black_title',
                209=>'solution_pre_title',
                210=>'solution_title',
                211=>'testimonial_position',
                212=>'department',
                213=>'header_title',
                214=>'people_title',
                215=>'people_subtitle'
            ),
            'attributes_multi_lang_textarea' => array(
                400=>'text',
                401=>'black_text',
                402=>'solution_text',
                403=>'testimonial_text'
            ),
            'attributes_textarea' => array(
                500=>'position'
            ),
            'attributes_text' => array(),
            'attributes_multi_lang_image' => array(),
            'attributes_image' => array(
                601=>'grid_picture',
                602=>'header_picture',
                603=>'logo_picture',
                604=>'carrousel_picture',
                605=>'solution_picture',
                606=>'testimonial_picture',
                607=>'main_picture',
                608=>'rollover_picture'
            ),
            'images_sizes' => array(
                601 => '430x263',
                602 => '1400x600',
                603 => '160x160',
                604 => '565x465',
                605 => '380x380',
                606 => '',
                607 => '147x204',
                608 => '147x204'
            ),
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
            'relations' => array(
                100001=>'10,20',
                100002=>'10,40',
                200001=>'20,30',
                500001=>'50,60'
            ),
            'relation_names' => array(
                100001=>array('case_studies', 'case_studies'),
                100002=>array('job_offers', 'job_offers'),
                200001=>array('case_studies_categories', 'case_studies_categories'),
                500001=>array('person', 'person')
            ),
            'attributes_classes' => array(
                10 =>'',
                20 =>'200,201-202,601-602,603-604,101-102,103-104,203-204,205-206,207-208,401,209-210,402,605-606,403,211',
                30=>'200',
                40=>'200,105,212-201,400',
                50=>'213,207-208,401,214-215',
                60=>'200,500,607-608'
            )
        );

        $params = array(
            'lang' => 'ca'
            , 'debug' => true
            , 'metadata' => true
            , 'show_inmediate_debug' => true
            , 'timings' => true
        );

        $Generator = new Generator($this->connection, $params);

        $editora = $Generator->createEditora($data);

        var_dump($editora); die;

    }
}