<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Omatech\Editora\Utils;


class Debug {

		var $enabled=true;

		function __construct($enabled=true)
		{
				$this->enabled=$enabled;			
		}

		function debug ($message) {// Inclou el missatge a $GLOBALS['debug'] si tenim el flag de debug activat
			if ($this->enabled) 
			{
				if (isset($_REQUEST['omatech_editora_utils_debug']))
				{
						$_REQUEST['omatech_editora_utils_debug'].=$message.chr(13).chr(10)."\n";
				}
				else
				{
						$_REQUEST['omatech_editora_utils_debug']="\n".$message.chr(13).chr(10)."\n";
				}
			}
		}

		//////////////////////////////////////////////////////////////////////////////////////////
		function clear_debug() {
			$_REQUEST['omatech_editora_utils_debug']='';
		}

		//////////////////////////////////////////////////////////////////////////////////////////
		function get_debug_messages() 
		{
			$ret='';
			if ($this->enabled)
			{
				$ret="\n********************DEBUG********************";
				$ret.=$_REQUEST['omatech_editora_utils_debug'];
				$ret.="********************END DEBUG********************\n";
			}
			return $ret;
		}		

		
}
