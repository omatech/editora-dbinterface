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

        parent::setUp();
    }

    public function testTruncateAllTablesSuccessfully()
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

        $this->Clear->truncateAllTables();

        $query_result = $this->connection->fetchAssoc($query);

        $this->assertEmpty($query_result);
    }

}