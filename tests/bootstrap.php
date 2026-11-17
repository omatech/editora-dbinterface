<?php
require dirname(__DIR__).'/vendor/autoload.php';
$host = getenv('TEST_DB_HOST');
if ($host !== false) {
    if (!preg_match('/^editora-dbinterface-test-[a-z0-9-]+$/D', $host)) throw new RuntimeException('Refusing a database outside the owned fixture.');
    $GLOBALS['conn'] = $conn = \Doctrine\DBAL\DriverManager::getConnection(['driver' => 'pdo_mysql', 'host' => $host, 'dbname' => 'editora_testing', 'user' => 'root', 'password' => 'fixture-only', 'charset' => 'utf8mb4', 'driverOptions' => [1002 => "SET time_zone = '+00:00'"]]);
    $conn->executeStatement(file_get_contents(dirname(__DIR__).'/data/editora.sql'));
    $conn->executeStatement("INSERT INTO omp_classes(id,name,tag) VALUES(1,'Global','global')");
}
