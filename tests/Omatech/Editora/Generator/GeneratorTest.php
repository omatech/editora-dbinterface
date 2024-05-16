<?php
/**
 * Created by Omatech
 * User: aroca@omatech.com
 * Date: 26/04/18 12:53
 */

require_once __DIR__ . '/../TestCaseBase.php';

use Omatech\Editora\Generator\Generator;
use Omatech\Editora\Clear\Clear;

class GeneratorTest extends TestCaseBase
{
    protected $Generator;

    protected function setUp()
    {
        $Clear = new Clear($this->conn, array());
        $Clear->dropAllData();

        $this->Generator = new Generator($this->conn, array());

        parent::setUp();
    }

    private function getTestData()
    {
        return array(
            'nomintern_id' => $this->Generator->editoraDefaultNomInternId(),
            'nomintern_name' => $this->Generator->editoraDefaultNomInternName(),
            'niceurl_id' => 2,
            'niceurl_name' => 'test-editora',
            'localized_attributes' => array(),
            'simple_attributes' => array(),
            'original_localized_attributes' => array(),
            'global_filas' => array(),
            'users' => array(
                array('test_editora', 'Administrator', 'en')
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
                    10=> 'Home'
                ),
                'Secondary'=> array(
                    30=> array('case_studies_categories', 'Categorías de casos de estudio'),
                    20=> array('case_studies', 'Casos de estudio'),
                    40=>'job_offers',
                    50=>'people_section',
                    60=>'person'
                )
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
//                200=> array('title', 'Título'), //TODO
                200=> 'title',
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
                100001=>array('case_studies', 'Caso de estudio'),
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
            ),
            'roles' => array(
                array('id' => 3, 'name' => 'testrole', 'classes' => '10,20,30'),
            )
        );
    }

    //Tests

    public function testGenerateEditoraSuccessfully()
    {
        $data = $this->getTestData();

        $created = $this->Generator->createEditora($data);

        $this->assertTrue($created);
    }

    public function testGenerateEditoraDbStructure()
    {
        $data = $this->getTestData();

        $created = $this->Generator->createEditora($data);

        $this->assertTrue($created);

        $dbname = env('DB_DATABASE', '');
        $required_tables = array(
            'omp_attributes',
            'omp_class_attributes',
            'omp_class_groups',
            'omp_classes',
            'omp_instances',
            'omp_instances_backup',
            'omp_instances_cache',
            'omp_lookups',
            'omp_lookups_values',
            'omp_niceurl',
            'omp_relation_instances',
            'omp_relations',
            'omp_roles',
            'omp_roles_classes',
            'omp_search',
            'omp_static_text',
            'omp_tabs',
            'omp_user_instances',
            'omp_users',
            'omp_values'
        );
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = '$dbname';";

        $query_result = $this->fetchAll($sql);

        $required_tables_in_results = 0;

        foreach ($query_result as $aTable) {
            if (in_array($aTable['table_name'], $required_tables)) {
                $required_tables_in_results++;
            }
        }

        $this->assertTrue(count($required_tables) == $required_tables_in_results);
    }

    //Tests nom intern
    
    public function testGenerateEditoraCheckNomIntern()
    {
        $data = $this->getTestData();
        $nomintern_name = $data['nomintern_name'];

        $this->Generator->createEditora($data);

        $query_result = $this->fetchAssoc("select * from omp_attributes a where a.name='$nomintern_name' limit 1;");

        $this->assertTrue(!empty($query_result['name']));
        $this->assertTrue(!empty($query_result['id']) && $query_result['id'] == $data['nomintern_id']);
    }

    public function testGenerateEditoraDefaultNomIntern()
    {
        $data = $this->getTestData();
        $data['nomintern_id'] = rand(100, 1000);
        $data['nomintern_name'] = 'another_name_'.rand();

        $nomintern_id = $data['nomintern_id'];
        $default_nomintern_id = $this->Generator->editoraDefaultNomInternId();
        $nomintern_name = $data['nomintern_name'];
        $default_nomintern_name = $this->Generator->editoraDefaultNomInternName();

        $this->Generator->createEditora($data);

        $query_result = $this->fetchAssoc("select * from omp_attributes a where a.name='$nomintern_name' limit 1;");

        $this->assertFalse($query_result);

        $query_result = $this->fetchAssoc("select * from omp_attributes a where a.id='$nomintern_id' limit 1;");

        $this->assertFalse($query_result);

        $query_result = $this->fetchAssoc("select * from omp_attributes a where a.name='$default_nomintern_name' limit 1;");

        $this->assertTrue(!empty($query_result['name']) && $query_result['name'] != $nomintern_name && $query_result['name'] == $default_nomintern_name);

        $query_result = $this->fetchAssoc("select * from omp_attributes a where a.id='$default_nomintern_id' limit 1;");

        $this->assertTrue(!empty($query_result['id']) && $query_result['id'] != $nomintern_id && $query_result['id'] == $default_nomintern_id);
    }

    public function testGenerateEditoraDataWithoutNomIntern()
    {
        $data = $this->getTestData();
        unset($data['nomintern_id']);
        unset($data['nomintern_name']);

        $default_nomintern_id = $this->Generator->editoraDefaultNomInternId();
        $default_nomintern_name = $this->Generator->editoraDefaultNomInternName();

        $this->Generator->createEditora($data);

        $query_result = $this->fetchAssoc("select * from omp_attributes a where a.name='$default_nomintern_name' limit 1;");

        $this->assertTrue(!empty($query_result['name']) && $query_result['name'] == $default_nomintern_name);

        $query_result = $this->fetchAssoc("select * from omp_attributes a where a.id='$default_nomintern_id' limit 1;");

        $this->assertTrue(!empty($query_result['id']) && $query_result['id'] == $default_nomintern_id);
    }

    //Tests languages
    
    public function testGenerateEditoraCheckLanguages()
    {
        $data = $this->getTestData();
        $languages = $data['languages'];

        $this->Generator->createEditora($data);

        $query_result = $this->fetchAll("SELECT language FROM omp_attributes where language != 'ALL' group by language;");

        $this->assertNotEmpty($query_result);
        $this->assertTrue(is_array($query_result));

        if (is_array($query_result)) {
            $dbLanguages = array();

            foreach ($query_result as $aResult) {
                array_push($dbLanguages, reset($aResult));
            }

            foreach ($languages as $aLanguage) {
                $this->assertTrue(in_array($aLanguage, $dbLanguages));
            }
        }
    }
    
    //Tests Roles

    public function testsGenerateEditoraCheckRoles()
    {
        $data = $this->getTestData();
        $testRoleId = rand(3, 10);
        $testRoleName = 'test'.rand(1, 10);
        $data['roles'] = array(array('id' => $testRoleId, 'name' => $testRoleName));

        $this->Generator->createEditora($data);

        $query_result = $this->fetchAll("select * from omp_roles;");

        $this->assertTrue(is_array($query_result));

        $new_role_exists = false;

        foreach ($query_result as $aRole) {
            if ($aRole['id'] == $testRoleId && $aRole['rol_name'] == $testRoleName) {
                $new_role_exists = true;
                break;
            }
        }

        $this->assertTrue($new_role_exists);
    }

    public function testsGenerateEditoraDefaultRoles()
    {
        $data = $this->getTestData();

        $this->Generator->createEditora($data);

        $query_result = $this->fetchAll("select * from omp_roles;");

        $this->assertTrue(is_array($query_result));

        $check_admin = $check_user = false;

        foreach ($query_result as $aRole) {
            if ($aRole['id'] == 1 && $aRole['rol_name'] == 'admin') {
                $check_admin = true;
            } elseif ($aRole['id'] == 2 && $aRole['rol_name'] == 'user') {
                $check_user = true;
            }
        }

        $this->assertTrue($check_admin);
        $this->assertTrue($check_user);
    }

    public function testsGenerateEditoraDataWithoutRoles()
    {
        $data = $this->getTestData();
        unset($data['roles']);

        $this->Generator->createEditora($data);

        $query_result = $this->fetchAll("select * from omp_roles;");

        $this->assertTrue(is_array($query_result));
        $this->assertTrue(count($query_result) == 2);

        $check_admin = $check_user = false;

        foreach ($query_result as $aRole) {
            if ($aRole['id'] == 1 && $aRole['rol_name'] == 'admin') {
                $check_admin = true;
            } elseif ($aRole['id'] == 2 && $aRole['rol_name'] == 'user') {
                $check_user = true;
            }
        }

        $this->assertTrue($check_admin);
        $this->assertTrue($check_user);
    }

    public function testsGenerateEditoraSaveRolesWithClasses()
    {
        $data = $this->getTestData();

        $randomNumOfNewClasses = rand(1, 4);

        $randomClassIds = array();

        for ($i = 0; $i < $randomNumOfNewClasses; $i++) {
            $randomClassId = rand(100*$i+101, 100*($i+1)+100);
            $randomClassName = 'testclass'.substr(md5(rand()), 0, 5);

            $data['classes']['Secondary'][$randomClassId] = $randomClassName;

            array_push($randomClassIds, $randomClassId);
        }

        $testRoleId = rand(3, 10);
        $testRoleName = 'test'.rand(1, 10);
        $testRoleClasses = implode(',', $randomClassIds);
        $data['roles'] = array(
            array(
                'id' => $testRoleId,
                'name' => $testRoleName,
                'classes' => $testRoleClasses
            )
        );

        $this->Generator->createEditora($data);

        $query_result = $this->fetchAll("select * from omp_roles_classes where rol_id IN ($testRoleId) and class_id IN ($testRoleClasses);");

        $this->assertTrue(is_array($query_result));
        $this->assertTrue(!empty($query_result));
        $this->assertTrue(count($query_result) == $randomNumOfNewClasses);

        $role_classes_relation_count = 0;

        foreach ($query_result as $aRoleClassRelation) {
            foreach ($randomClassIds as $aRandomClassIdKey => $aRandomClassId) {
                if ($aRoleClassRelation['class_id'] == $aRandomClassId) {
                    $role_classes_relation_count++;
                    unset($randomClassIds[$aRandomClassIdKey]);
                    break;
                }
            }
        }

        $this->assertTrue($role_classes_relation_count == $randomNumOfNewClasses);
    }

    public function testsGenerateEditoraSaveRolesWithoutClasses()
    {
        $data = $this->getTestData();

        $testRoleId = rand(3, 10);
        $testRoleName = 'test'.rand(1, 10);
        $data['roles'] = array(
            array(
                'id' => $testRoleId,
                'name' => $testRoleName,
                'classes' => ''
            )
        );

        $this->Generator->createEditora($data);

        $query_result = $this->fetchAll("select * from omp_roles_classes where rol_id IN ($testRoleId);");

        $this->assertTrue(is_array($query_result));
        $this->assertTrue(!empty($query_result));
    }

    //Captions

    public function testGenerateEditoraSaveClassesCaptions()
    {
        $data = $this->getTestData();
        $class_id = 40;
        $name_ca = $name_es = $name_en = 'Ofertas de trabajo';
        $data['classes']['Secondary'][$class_id] = array('job_offers', $name_ca);

        $this->Generator->createEditora($data);

        $query_result = $this->fetchAll("select * from omp_classes where id=$class_id;");

        $this->assertTrue(isset($query_result[0]));
        $this->assertTrue($query_result[0]['name_ca'] == $name_ca);
        $this->assertTrue($query_result[0]['name_es'] == $name_es);
        $this->assertTrue($query_result[0]['name_en'] == $name_en);
    }

    //validation data

    public function testGenerateEditoraWithoutUserInData()
    {
        $data = $this->getTestData();
        unset($data['users']);

        $generated = $this->Generator->createEditora($data);

        $this->assertTrue($generated);
    }

//    public function testGenerateEditoraWithUserOmatech()
//    {
//        $data = $this->getTestData();
//        unset($data['users']);
//
//        $data['users'] = array(
//            array('omatech', 'UserTest', 'ca')
//        );
//
//        $generated = $this->Generator->createEditora($data);
//
//        $this->assertTrue($generated);
//
//        $query_result = $this->fetchAll("select * from omp_users WHERE username='omatech';");
//
//        $usernameOmatechCount = 0;
//        foreach ($query_result as $anUser)
//        {
//            if($anUser['username'] == 'omatech'){
//                $usernameOmatechCount++;
//            }
//            if($usernameOmatechCount > 1){
//                break;
//            }
//        }
//
//        $this->assertEquals(1, $usernameOmatechCount);
//    }

    public function testGenerateEditoraWithoutUserOmatech()
    {
        $data = $this->getTestData();
        unset($data['users']);

        $data['users'] = array(
            array('test', 'UserTest', 'ca')
        );

        $generated = $this->Generator->createEditora($data);

        $this->assertTrue($generated);

        $query_result = $this->fetchAll("select * from omp_users WHERE username='omatech';");

        foreach ($query_result as $anUser) {
            if ($anUser['username'] == 'omatech') {
                $this->assertTrue(true);
                break;
            }
        }
    }
}
