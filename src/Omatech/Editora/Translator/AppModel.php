<?php

namespace Omatech\Editora\Translator;

class AppModel {

	public $conn_from;
	public $conn_to;
	protected $debug;
	public $debug_messages;

	function __construct($conn_from = null, $conn_to = null) {
		if (is_array($conn_from)) {
			$config = new \Doctrine\DBAL\Configuration();
			//..
			$connectionParams = array(
				'dbname' => $conn_from['dbname'],
				'user' => $conn_from['dbuser'],
				'password' => $conn_from['dbpass'],
				'host' => $conn_from['dbhost'],
				'driver' => 'pdo_mysql',
				'charset' => 'utf8'
			);
			$conn_from = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
		}
		$this->conn_from = $conn_from;
		
		if (is_array($conn_to)) {
			$config = new \Doctrine\DBAL\Configuration();
			//..
			$connectionParams = array(
				'dbname' => $conn_to['dbname'],
				'user' => $conn_to['dbuser'],
				'password' => $conn_to['dbpass'],
				'host' => $conn_to['dbhost'],
				'driver' => 'pdo_mysql',
				'charset' => 'utf8'
			);
			$conn_to = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
		}
		$this->conn_to = $conn_to;
		
	}

	protected function debug($str) {
		if ($this->debug) {
			$this->debug_messages .= $str;
		}
	}

	function start_transaction($connection='conn_from') {
		$sql = "start transaction";
		$this->$connection->executeQuery($sql);
	}

	function commit($connection='conn_from') {
		$sql = "commit";
		$this->$connection->executeQuery($sql);
	}

	function rollback($connection='conn_from') {
		$sql = "rollback";
		$this->$connection->executeQuery($sql);
	}

	function truncate_table($table_name, $connection='conn_to') {
		$sql = "truncate table $table_name";
		$this->$connection->executeQuery($sql);
	}	
	
	function fetchAll ($sql, $connection='conn_from')
	{
		$rows=$this->$connection->fetchAll($sql);
		return $rows;		
	}
	
	function fetchColumn ($sql, $connection='conn_from')
	{
		$rows=$this->$connection->fetchColumn($sql);
		return $rows;		
	}

	function get_one ($sql, $connection='conn_from')
	{
		$rows=$this->$connection->fetchAll($sql);
		if ($rows && $rows[0]) return $rows[0];
		return false;
	}
	
	function escape ($str, $connection='conn_from')
	{
		return $this->$connection->quote($str);
	}
	
	function insert_one ($sql, $connection='conn_from')
	{
		$this->$connection->executeQuery($sql);
		return $this->$connection->lastInsertId();
	}
	
	function executeQuery ($sql, $connection='conn_from')
	{
		$this->$connection->executeQuery($sql);
	}
	
	function executeUpdate ($sql, $connection='conn_from')
	{
		return $this->$connection->executeUpdate($sql);
	}
}
