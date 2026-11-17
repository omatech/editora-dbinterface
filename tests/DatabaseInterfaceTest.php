<?php

use Doctrine\DBAL\DriverManager;
use Omatech\Editora\DBInterfaceBase;
use PHPUnit\Framework\TestCase;

final class DatabaseInterfaceTest extends TestCase
{
    public function test_class_filters_quote_values_and_read_through_dbal4(): void
    {
        $db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $db->executeStatement('CREATE TABLE omp_classes(id INTEGER, name TEXT, tag TEXT)');
        $db->insert('omp_classes', ['id' => 1, 'name' => 'Home', 'tag' => 'home']);
        $db->insert('omp_classes', ['id' => 2, 'name' => 'Private', 'tag' => 'private']);
        $interface = new DBInterfaceBase($db);
        self::assertSame(1, $interface->findClass('home')['class_id']);
        self::assertNull($interface->findClass("missing' OR 1=1 --"));
        self::assertSame([['class_id' => 1, 'name' => 'Home']], $interface->getAllClasses('1'));
        self::assertCount(2, $interface->getAllClasses());
        $db->close();
    }
}
