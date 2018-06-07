<?php
/**
 * Created by Omatech
 * Date: 02/05/18 10:47
 */

namespace Omatech\Editora\Clear;

use Omatech\Editora\DBInterfaceBase;

class Clear extends DBInterfaceBase
{

    /**
     * Drop all data from non-sensitive tables.
     */
    public function truncateTables()
    {
        $database_name = $this->conn->getDatabase();
        $tables_to_truncate = array(
            'omp_attributes',
            'omp_class_attributes',
            'omp_class_groups',
            'omp_classes',
            'omp_lookups',
            'omp_lookups_values',
            'omp_relations',
            'omp_roles',
            'omp_roles_classes',
            'omp_tabs',
        );

        $tables_truncate_queries = '';

        foreach ($tables_to_truncate as $aTable)
        {
            $tables_truncate_queries .= 'DROP TABLE '.$database_name.'.'.$aTable.';';
        }

        $commands = 'SET FOREIGN_KEY_CHECKS=0;'.$tables_truncate_queries.'SET FOREIGN_KEY_CHECKS=1;';

        $this->conn->executeQuery($commands);

        $editora_structure = file_get_contents(__DIR__ .'/../../../../sql/editora.sql');

        $this->conn->executeQuery($editora_structure);
    }

    /**
     * Drop all data from database.
     */
    public function dropAllData()
    {
        $database_name = $this->conn->getDatabase();

        $tables = $this->conn->query("SELECT concat('DROP TABLE IF EXISTS ', table_name, ';') FROM information_schema.tables WHERE table_schema = '$database_name';")->fetchAll();

        $queries = 'SET FOREIGN_KEY_CHECKS=0;';

        foreach ($tables as $aTable){
            $queries .= reset($aTable);
        }

        $this->conn->executeQuery($queries);

        $editora_structure = file_get_contents(__DIR__ .'/../../../../sql/editora.sql');

        $this->conn->executeQuery($editora_structure);
    }

}