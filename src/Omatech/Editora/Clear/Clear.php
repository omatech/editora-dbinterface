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
    public function truncateAllTables()
    {
        $database_name = $this->conn->getDatabase();

        $tables_query = 'SELECT concat(\'TRUNCATE TABLE \',table_schema,\'.\',TABLE_NAME, \';\') FROM INFORMATION_SCHEMA.TABLES Where table_schema IN (\''.$database_name.'\')';
        $tables_truncate_queries_array = $this->conn->fetchAll($tables_query);
        $tables_truncate_queries = '';

        foreach ($tables_truncate_queries_array as $aTableTruncateQuery)
        {
            $tables_truncate_queries .= reset($aTableTruncateQuery);
        }

        $commands = 'SET FOREIGN_KEY_CHECKS=0;'.$tables_truncate_queries.'SET FOREIGN_KEY_CHECKS=1;';

        $this->conn->executeQuery($commands);
    }

}