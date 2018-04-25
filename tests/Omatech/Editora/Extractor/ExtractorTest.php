<?php

//declare(strict_types = 1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
//echo __DIR__."\n";die;


$autoload_location = '/vendor/autoload.php';
while (!is_file(__DIR__.$autoload_location)) { $autoload_location='/..'.$autoload_location;}

$config_location = '/conf/config.php';
while (!is_file(__DIR__.$config_location)) { $config_location='/..'.$config_location;}

$bootstrap_location = '/conf/bootstrap.php';
while (!is_file(__DIR__.$bootstrap_location)) { $bootstrap_location='/..'.$bootstrap_location;}

require_once __DIR__.$autoload_location;
require_once __DIR__.$config_location;
require_once __DIR__.$bootstrap_location;

use PHPUnit\Framework\TestCase;
use Omatech\Editora\Extractor\Extractor;

final class ExtractorTest extends PHPUnit_Framework_TestCase {

    public function testHello() 
		{
        $this->assertEquals('hello', 'hello');
    }
		
		public function testProblemaID(){
      global $conn;
			$id=-1;
        $params = [
          'lang' => 'ca'
          , 'debug' => true
          , 'metadata' => true
					, 'show_inmediate_debug' => true
					, 'timings' => true
        ];			
				
				$e=new Extractor($conn, $params);   
				$res=$e->findInstanceById($id, null, function($i) use ($e)
				{//page
					$submenus=$e->findRelatedInstances($i, 'obc_section_pages', 1, ['alias'=>'submenu'], function($i) use ($e){
						return $e->findRelatedInstances($i, 'obc_section_pages', 100, ['direction'=>'child', 'alias'=>'pages']);
					});
					//print_r($submenus);die;
					$groups=$e->findRelatedInstances($i, 'page_groupsmusicians', 100, ['alias'=>'groups'], function($i) use ($e)
						{
						  return $e->findChildrenInstances($i, 'group_musicians', 100, ['alias'=>'musicians']);
						});
				  return array_merge($submenus, $groups);	
				});
				
				
				$this->assertEquals(array(), $res);
				
		}				

		public function testOBC2(){
      global $conn;
			
			$start= microtime(true);
			
			$id=25116;
        $params = [
          'lang' => 'ca'
          , 'debug' => true
          , 'metadata' => true
					, 'show_inmediate_debug' => true
					, 'timings' => true
        ];			
				
				$e=new Extractor($conn, $params);
				$res=$e->findInstanceById($id, null, function($i) use ($e)
				{//page
					$submenus=$e->findRelatedInstances($i, 'obc_section_pages', 1, ['alias'=>'submenu'], function($i) use ($e){
						return $e->findRelatedInstances($i, 'obc_section_pages', 100, ['direction'=>'child', 'alias'=>'pages']);
					});
					//print_r($submenus);die;
					$groups=$e->findRelatedInstances($i, 'page_groupsmusicians', 100, ['alias'=>'groups'], function($i) use ($e)
						{
						  return $e->findChildrenInstances($i, 'group_musicians', 100, ['alias'=>'musicians']);
						});
				  return array_merge($submenus, $groups);	
				});
				
				$end=microtime(true);
				$total=$end-$start;
				echo "Tiempo total $total segundos";
				print_r($res);
		}


		
}
