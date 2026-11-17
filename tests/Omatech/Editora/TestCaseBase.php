<?php

/**
 * Created by Omatech
 * Date: 25/04/2018 13:44
 */
/**
* @backupGlobals disabled
* @backupStaticAttributes disabled
*/
//declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

class TestCaseBase extends TestCase
{
    protected $conn;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conn = $GLOBALS['conn'] ?? throw new RuntimeException('The MySQL suite requires tests/mysql-check.sh.');
        (new \Omatech\Editora\Clear\Clear($this->conn))->dropAllData();
        $this->conn->insert('omp_classes', ['id' => 1, 'name' => 'Global', 'tag' => 'global']);
    }

    protected function fetchAssoc($sql)
    {
        if (method_exists($this->conn, 'fetchAssoc')) {
            return $this->conn->fetchAssoc($sql);
        } else {
            return $this->conn->executeQuery($sql)->fetchAssociative();
        }
    }

    protected function fetchAll($sql)
    {
        if (method_exists($this->conn, 'fetchAll')) {
            return $this->conn->fetchAll($sql);
        } else {
            return $this->conn->executeQuery($sql)->fetchAllAssociative();
        }
    }

    protected function fetchColumn($sql)
    {
        $row=$this->fetchAssoc($sql);
        return $row[key($row)];
    }
}
