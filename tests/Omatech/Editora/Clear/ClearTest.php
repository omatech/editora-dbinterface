<?php
/**
 * Created by Omatech
 * Date: 02/05/18 10:47
 */

require_once __DIR__ . '/../TestCaseBase.php';

use Omatech\Editora\Clear\Clear;

class ClearTest extends TestCaseBase
{
    protected $Clear;

    protected function setUp()
    {
        $this->Clear = new Clear($this->connection, array());
        $this->Clear->dropAllData();

        parent::setUp();
    }

    public function testTruncateTablesSuccessfully()
    {
        $class = 'test_class_'.rand();
        $query = "select c.id class_id, c.name, c.tag from omp_classes c where c.tag='$class' limit 1;";
        $this->connection->insert($this->connection->getDatabase().'.omp_classes', array(
           'name' => $class,
           'tag' => $class,
        ));
        $query_result = $this->connection->fetchAssoc($query);
				

        if(is_array($query_result) && !empty($query_result['name'])){
            $this->assertArrayHasKey('name', $query_result);
            $this->assertTrue($query_result['name'] == $class);
        }else{
            $this->assertTrue(false);
        }

        $this->Clear->truncateTables();

        $query_result = $this->connection->fetchAssoc($query);

        $this->assertEmpty($query_result);
    }

		
    public function testTableOmpInstancesNotTruncated()
    {
        $class = 'test_class_'.rand();
        $query = "select c.id class_id, c.name, c.tag from omp_classes c where c.tag='$class' limit 1;";
        $this->connection->insert($this->connection->getDatabase().'.omp_classes', array(
            'name' => $class,
            'tag' => $class,
        ));
        $query_result = $this->connection->fetchAssoc($query);

        if(is_array($query_result) && !empty($query_result['name']) && !empty($query_result['class_id'])){
            $this->assertArrayHasKey('name', $query_result);
            $this->assertTrue($query_result['name'] == $class);
        }else{
            $this->assertTrue(false);
        }

        $class_id = $query_result['class_id'];

        $this->connection->insert($this->connection->getDatabase().'.omp_instances', array(
            'class_id' => $class_id,
        ));

        $this->Clear->truncateTables();

        $query_result = $this->connection->fetchAssoc($query);

        $this->assertEmpty($query_result);

        $query_result = $this->connection->fetchAssoc("select omp_instances.id, omp_instances.class_id from omp_instances where omp_instances.class_id='$class_id' limit 1;");

        $this->assertNotEmpty($query_result['class_id']);
        $this->assertEquals($class_id, $query_result['class_id']);
    }

    public function testDropAllData()
    {
        $class = 'test_class_'.rand();
        $query = "select c.id class_id, c.name, c.tag from omp_classes c where c.tag='$class' limit 1;";
        $this->connection->insert($this->connection->getDatabase().'.omp_classes', array(
            'name' => $class,
            'tag' => $class,
        ));
        $query_result = $this->connection->fetchAssoc($query);

        if(is_array($query_result) && !empty($query_result['name']) && !empty($query_result['class_id'])){
            $this->assertArrayHasKey('name', $query_result);
            $this->assertTrue($query_result['name'] == $class);
        }else{
            $this->assertTrue(false);
        }

        $class_id = $query_result['class_id'];

        $this->connection->insert($this->connection->getDatabase().'.omp_instances', array(
            'class_id' => $class_id,
        ));

        $this->Clear->dropAllData();

        $query_result = $this->connection->fetchAssoc($query);

        $this->assertEmpty($query_result);

        $query_result = $this->connection->fetchAssoc("select omp_instances.id, omp_instances.class_id from omp_instances where omp_instances.class_id='$class_id' limit 1;");

        $this->assertEmpty($query_result);
    }

}