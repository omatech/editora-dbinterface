<?php

require_once __DIR__ . '/../TestCaseBase.php';

use Omatech\Editora\Extractor\Extractor;

final class ExtractorTest extends TestCaseBase
{

    public function testHello()
    {
        $this->assertEquals('hello', 'hello');
    }
		
		public function testConnection()
		{
			$row=$this->fetchAssoc("select * from omp_classes where id=1");
			$this->assertEquals(1, $row['id']);
			$this->assertEquals('Global', $row['name']);
		}


}
