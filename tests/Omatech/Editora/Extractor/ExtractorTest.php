<?php

require_once __DIR__ . '/../TestCaseBase.php';

use Omatech\Editora\Extractor\Extractor;

final class ExtractorTest extends TestCaseBase
{

    public function testHello()
    {
        $this->assertEquals('hello', 'hello');
    }

    public function testProblemaID()
    {
        $id = -1;
        $params = [
            'lang' => 'ca'
            , 'debug' => true
            , 'metadata' => true
            , 'show_inmediate_debug' => true
            , 'timings' => true
        ];

        $e = new Extractor($this->connection, $params);
        $res = $e->findInstanceById($id, null, function ($i) use ($e) {//page
            $submenus = $e->findRelatedInstances($i, 'obc_section_pages', 1, ['alias' => 'submenu'], function ($i) use ($e) {
                return $e->findRelatedInstances($i, 'obc_section_pages', 100, ['direction' => 'child', 'alias' => 'pages']);
            });
            //print_r($submenus);die;
            $groups = $e->findRelatedInstances($i, 'page_groupsmusicians', 100, ['alias' => 'groups'], function ($i) use ($e) {
                return $e->findChildrenInstances($i, 'group_musicians', 100, ['alias' => 'musicians']);
            });
            return array_merge($submenus, $groups);
        });

        $this->assertEquals(array(), $res);

    }

    public function testOBC2()
    {
        $start = microtime(true);

        $id = 25116;
        $params = [
            'lang' => 'ca'
            , 'debug' => true
            , 'metadata' => true
            , 'show_inmediate_debug' => true
            , 'timings' => true
        ];

        $e = new Extractor($this->connection, $params);
        $res = $e->findInstanceById($id, null, function ($i) use ($e) {//page
            $submenus = $e->findRelatedInstances($i, 'obc_section_pages', 1, ['alias' => 'submenu'], function ($i) use ($e) {
                return $e->findRelatedInstances($i, 'obc_section_pages', 100, ['direction' => 'child', 'alias' => 'pages']);
            });
            //print_r($submenus);die;
            $groups = $e->findRelatedInstances($i, 'page_groupsmusicians', 100, ['alias' => 'groups'], function ($i) use ($e) {
                return $e->findChildrenInstances($i, 'group_musicians', 100, ['alias' => 'musicians']);
            });
            return array_merge($submenus, $groups);
        });

        $end = microtime(true);
        $total = $end - $start;
        echo "Tiempo total $total segundos";
        print_r($res);
    }
}
