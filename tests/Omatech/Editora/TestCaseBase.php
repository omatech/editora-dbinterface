<?php
/**
 * Created by Omatech
 * Date: 25/04/18 13:44
 */


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

class TestCaseBase extends PHPUnit_Framework_TestCase
{
    protected $connection;

    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        global $conn;
        $this->connection = $conn;

        parent::__construct($name, $data, $dataName);
    }

    public function testConnection()
    {
        //TODO
        $this->assertTrue(true);
    }

}