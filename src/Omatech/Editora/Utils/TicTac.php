<?php
namespace Omatech\Editora\Utils;


class TicTac 
{
		var $enabled=true;

		function __construct($enabled=true)
		{
				$this->enabled=$enabled;			
		}

		function tic($id) 
		{// Keeps the start time in a global variable named after the id
			if ($this->enabled) 
			{
				$_REQUEST['omatech_editora_utils_timings_start_'.$id]=microtime(true);
			}
		}

		//////////////////////////////////////////////////////////////////////////////////////////
		function tac($id) { // Keeps the end time in a global variable named after the id
			if ($this->enabled) 
			{
				$_REQUEST['omatech_editora_utils_timings_end_'.$id]=microtime(true);
			}
		}

		//////////////////////////////////////////////////////////////////////////////////////////
		function get_time($id) {
			if ($this->enabled) 
			{
				if (isset($_REQUEST['omatech_editora_utils_timings_start_'.$id]) && isset($_REQUEST['omatech_editora_utils_timings_end_'.$id])) {
					$start_microtime=$_REQUEST['omatech_editora_utils_timings_start_'.$id];
					$end_microtime=$_REQUEST['omatech_editora_utils_timings_end_'.$id];
					$total_time=round(($end_microtime-$start_microtime)*1000, 4);
					return $total_time;
				}
			}  
		}

		function get_full_stats() 
		{
			$ret='';
			if ($this->enabled)
			{
				$ret="\n********************TIMINGS********************\n";
				$anterior=array();
				$global_array=array();
				$i=0;

				foreach ($_REQUEST as $key=>$value) { // per cada variable global comprovem si es de timing, si es aixi l'afegim a la sortida
					if (stripos($key, 'omatech_editora_utils_timings_start')!==false) {
						$global_array[$i]=substr($key,36);
						$i++;
					}
				}

				for ($x=0;$x<$i;$x++) {
					$ret.=$global_array[$x]."\n";
					$ret.='Start: '.$_REQUEST['omatech_editora_utils_timings_start_'.$global_array[$x]].'  End: '.$_REQUEST['omatech_editora_utils_timings_end_'.$global_array[$x]].' Time: '.round(($_REQUEST['omatech_editora_utils_timings_end_'.$global_array[$x]]-$_REQUEST['omatech_editora_utils_timings_start_'.$global_array[$x]])*1000,4)."ms (".round(($_REQUEST['omatech_editora_utils_timings_end_'.$global_array[$x]]-$_REQUEST['omatech_editora_utils_timings_start_'.$global_array[$x]]),4)." s)\n\n";
				}
				$ret.="********************END TIMINGS********************\n";
			}
			return $ret;
		}

}
