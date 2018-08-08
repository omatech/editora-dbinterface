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

class TestCaseBase extends PHPUnit_Framework_TestCase {

	protected $connection;

	public function __construct($name = NULL, array $data = array(), $dataName = '') 
	{
		global $conn;
		$this->connection = $conn;
		parent::__construct($name, $data, $dataName);
	}


}
