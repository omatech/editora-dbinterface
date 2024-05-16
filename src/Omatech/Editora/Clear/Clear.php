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
    public function truncateTables($truncate_users=false)
    {
        $database_name = env('DB_DATABASE', '');
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

        if ($truncate_users)
        {
            $tables_to_truncate[]='omp_users';
        }

        $tables_truncate_queries = '';

        foreach ($tables_to_truncate as $aTable)
        {
            $tables_truncate_queries .= "DROP TABLE IF EXISTS $database_name.$aTable;\n";
        }

        $commands = "SET FOREIGN_KEY_CHECKS=0;\n".$tables_truncate_queries."SET FOREIGN_KEY_CHECKS=1;\n";

        $this->conn->executeQuery($commands);

        $editora_structure = file_get_contents(__DIR__ .'/../../../../data/editora.sql');

        $this->conn->executeQuery($editora_structure);
    }

    /**
     * Drop all data from database.
     */
    public function dropAllData()
    {
        $database_name = env('DB_DATABASE', '');

        $tables = $this->conn->query("SELECT concat('DROP TABLE IF EXISTS ', table_name, ';') FROM information_schema.tables WHERE table_schema = '$database_name';")->fetchAll();

        $queries = "SET FOREIGN_KEY_CHECKS=0;\n";

        foreach ($tables as $aTable){
            $queries .= reset($aTable);
        }

        $this->conn->executeQuery($queries);

        $editora_structure = file_get_contents(__DIR__ .'/../../../../data/editora.sql');

        $this->conn->executeQuery($editora_structure);
    }
		
		public function deleteAllContentExceptHomeAndGlobal()
		{
			$this->conn->executeQuery("delete from omp_instances where id not in (1,2)");
			$this->conn->executeQuery("delete from omp_instances_backup");
			$this->conn->executeQuery("delete from omp_instances_cache");
			$this->conn->executeQuery("delete from omp_niceurl where inst_id not in (1,2)");
			$this->conn->executeQuery("delete from omp_relation_instances");
			$this->conn->executeQuery("delete from omp_search");
			$this->conn->executeQuery("delete from omp_static_text");
			$this->conn->executeQuery("delete from omp_user_instances where inst_id not in (1,2)");
			$this->conn->executeQuery("delete from omp_values where inst_id not in (1,2)");
		}
		
		public function deleteAllContent()
		{
			$this->conn->executeQuery("delete from omp_instances");
			$this->conn->executeQuery("delete from omp_instances_backup");
			$this->conn->executeQuery("delete from omp_instances_cache");
			$this->conn->executeQuery("delete from omp_niceurl");
			$this->conn->executeQuery("delete from omp_relation_instances");
			$this->conn->executeQuery("delete from omp_search");
			$this->conn->executeQuery("delete from omp_static_text");
			$this->conn->executeQuery("delete from omp_user_instances");
			$this->conn->executeQuery("delete from omp_values");
		}		

}