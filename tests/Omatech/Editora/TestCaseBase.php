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

class TestCaseBase extends PHPUnit_Framework_TestCase
{
    protected $conn;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        global $conn;
        $this->conn = $conn;
        parent::__construct($name, $data, $dataName);
    }

    protected function fetchAssoc($sql)
    {
        if (method_exists($this->conn, 'fetchAssoc')) {
            return $this->conn->fetchAssoc($sql);
        } else {
            if (method_exists($this->conn, 'query')) {
                if (method_exists($this->conn, 'fetchAssociative')) {
                    return $this->conn->query($sql)->fetchAssociative();
                } else {
                    return $this->conn->query($sql)->fetch(PDO::FETCH_ASSOC);
                }
            } else {
                return $this->conn->executeQuery($sql)->fetchAssociative();
            }
        }
    }

    protected function fetchAll($sql)
    {
        if (method_exists($this->conn, 'fetchAll')) {
            return $this->conn->fetchAll($sql);
        } else {
            if (method_exists($this->conn, 'query')) {
                return $this->conn->query($sql)->fetchAll();
            } else {
                return $this->conn->executeQuery($sql)->fetchAllAssociative();
            }
            
        }
    }

    protected function fetchColumn($sql)
    {
        $row=$this->fetchAssoc($sql);
        return $row[key($row)];
    }
}
