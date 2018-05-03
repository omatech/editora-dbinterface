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
     * Remove all data from database without deleting tables.
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
            'omp_roles_classes',
            'omp_tabs',
            'omp_users'
        );

        $tables_truncate_queries = '';

        foreach ($tables_to_truncate as $aTable)
        {
            $tables_truncate_queries .= 'TRUNCATE TABLE '.$database_name.'.'.$aTable.';';
        }

        $commands = 'SET FOREIGN_KEY_CHECKS=0;'.$tables_truncate_queries.'SET FOREIGN_KEY_CHECKS=1;';

        $this->conn->executeQuery($commands);
    }

}