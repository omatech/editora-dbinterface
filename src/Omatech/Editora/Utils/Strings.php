<?php

namespace Omatech\Editora\Utils;

class Strings {

		static function utf8_substr($str,$start)
		{
			 preg_match_all("/./su", $str, $ar);

			 if(func_num_args() >= 3) 
			 {
				 $end = func_get_arg(2);
				 return join("",array_slice($ar[0],$start,$end));
			 } 
			 else 
			 {
				 return join("",array_slice($ar[0],$start));
			 }
		}
		
		static function not_empty(&$var = null) 
		{
				if (!isset($var)) return false;
				if (is_null($var)) return false;
				if (empty($var)) return false;
				if (is_array($var) && sizeof($var) == 0) return false;
				if (is_object($var) && count(get_object_vars($var))==0) return false;
				if ($var === false) return false;

				return true;
		}


		static function urlnicify( $url, $id = '') 
		{
			if ('' == $url) return $url;
			$url=trim($url);
			$url=strip_tags($url);
			$url=self::filter_text($url);
			$temp=explode("/",$url);
			$url=$temp[count($temp)-1];


			$url = preg_replace('|[^a-z0-9-~+_. #=&;,/:]|i', '', $url);
			$url = str_replace('/', '', $url);
			$url = str_replace(' ', '-', $url);
			$url = str_replace('&', '', $url);
			$url = str_replace("'", "", $url);
			$url = str_replace(';//', '://', $url);
			$url = preg_replace('/&([^#])(?![a-z]{2,8};)/', '&#038;$1', $url);

			$url=strtolower($url);

			//Últims canvis
			$url = trim(mb_eregi_replace("[^ A-Za-z0-9_-]", "", $url)); 
			$url = mb_eregi_replace("[ \t\n\r]+", "-", $url);
			$url = mb_eregi_replace("[ -]+", "-", $url);
			$url=trim($url, '-');

			if ($id == '')
				return $url;

			return $url."-".$id;
		}

		///////////////////////////////////////////////////////////////////////////////////////////////
		static function filter_text ($original) {
				$search = array(
				"à", "á", "â", "ã", "ä", "À", "Á", "Â", "Ã", "Ä",
				"è", "é", "ê", "ë", "È", "É", "Ê", "Ë",
				"ì", "í", "î", "ï", "Ì", "Í", "Î", "Ï",
				"ó", "ò", "ô", "õ", "ö", "Ó", "Ò", "Ô", "Õ", "Ö",
				"ú", "ù", "û", "ü", "Ú", "Ù", "Û", "Ü",
				",", ".", ";", ":", "`", "´", "<", ">", "?", "}",
				"{", "ç", "Ç", "~", "^", "Ñ", "ñ"
				);
				$change = array(
				"a", "a", "a", "a", "a", "A", "A", "A", "A", "A",
				"e", "e", "e", "e", "E", "E", "E", "E",
				"i", "i", "i", "i", "I", "I", "I", "I",
				"o", "o", "o", "o", "o", "O", "O", "O", "O", "O",
				"u", "u", "u", "u", "U", "U", "U", "U",
				" ", " ", " ", " ", " ", " ", " ", " ", " ", " ",
				" ", "c", "C", " ", " ", "NY", "ny"
				);

				$filtered = strtoupper(str_ireplace($search,$change,$original));
				return $filtered;
		}

		static function StartsWith($Haystack, $Needle)
		{
			return strpos($Haystack, $Needle) === 0;
		}


		static function substring_before($haystack, $needle)
		{
			if ($i=stripos($haystack, $needle))
			{// Needle found, return from start to needle
				return(substr($haystack, 0, $i));
			}
			else
			{// Needle not found
				return $haystack;
			}
		}

		static function substring_after($haystack, $needle)
		{
			if ($i=strripos($haystack, $needle))
			{// Needle found, return from start to needle
				return(substr($haystack, $i+1));
			}
			else
			{// Needle not found
				return $haystack;
			}
		}


		static function hyperlink($text)
		{// $1 : protocolo, $2 : [www] + dominio, $3 : resto url
			if (strpos($text, '<img src="http://')===false && strpos($text, '<a href="http://')===false)
			{ 
				$text = preg_replace("/([a-zA-Z]+:\/\/)([a-z][a-z0-9\.\_\-]*[a-z]{2,6})([a-zA-Z0-9\/\*\-\?\&\%\=\#\_\;\,\(\)\.]*)/i", "<a href=\"$1$2$3\" target=\"_blank\" rel=\"nofollow\">$2</a> ", $text);
			}
			return $text;
		} 

		
		static function neteja ($str)
		{
			$strret=$str;
			$strret=str_replace('&amp;', '&', $strret);
			$strret=str_replace('&#39;', '\'', $strret);
			$strret=str_replace('&#40;', '(', $strret);
			$strret=str_replace('&#41;', ')', $strret);
			$strret=str_replace('&#45;', '-', $strret);

			if (strpos($strret, 'synerquia')===false)
			{
				$strret=hyperlink($strret);  
			}
			return $strret;
		}

		
		static function get_title_cutted($titol, $paraules=7, $caracters=47)
		{
			$res = "";
			$titolx = rtrim($titol," \t.");

			if( strlen($titolx) <= $caracters )
			{
				$res = $titolx;
			}
			else
			{
				$caracters = get_real_limit($titol, $caracters);
				//echo $titol." - ".$caracters;
				if (strlen($titolx)<=$caracters){// retorno directament l'string, es prou curt
					$res=$titolx;
				}
				else
				{// es massa llarg, recorrem l'array i parem quan no pugem mes
					$arr=explode(" ",$titolx);
					$cont=0;
					foreach($arr as $paraula)
					{
						if ((strlen($res)+strlen($paraula)+1)<=$caracters)
						{
								if ($cont!=0) $res.=' ';
							$res.=$paraula;
						}
						else
						{
							$res.='...';
							break;
						}
						$cont++;
					}
				}
			}

			return $res;
		}



		
		static function num_chars ($str, $chars)
		{
			$chars_arr=split(',', $chars);
			$count=0;

			foreach ($chars_arr as $char)
			{// for each char, lets see the number of occurrences in string
				if($char=="*") $char=",";
				$count+=strlen($str)-strlen(str_replace($char, '', $str));
			}

			return $count; 
		}



		static function get_real_limit($str, $limit, $very_small_factor=0.7, $small_factor=0.4, $large_factor=0.4, $very_large_factor=0.6)
		{
			$very_small=num_chars(substr($str,0,$limit+10), '*,\',f,i,í,ì,j,l,t,(,)');
			$small=num_chars(substr($str,0,$limit+10), 'r,s,z,J,I,Í,Ì, ');
			$large=num_chars(substr($str,0,$limit+10), 'a,b,d,e,g,o,p,q,F,L,P,R,S,T,Y,Z');
			$very_large=num_chars(substr($str,0,$limit+10), 'm,w,A,Á,À,B,C,D,E,G,H,K,M,N,Ñ,O,Ó,Ò,Q,U,Ú,Ú,V,W,X');

			/*  echo '<br />tots els molt curts='.$very_small;
			 echo '<br />tots els curts='.$small;
			 echo '<br />tots els llargs='.$large;
			 echo '<br />tots els molt llargs='.$very_large;
			*/
			$limit=$limit+($very_small*$very_small_factor)+($small*$small_factor);
			$limit=$limit-($large*$large_factor)-($very_large*$very_large_factor);
			$limit=round($limit);

			return $limit;
		}

		static function check_email($email) 
		{
			if(preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $email))
			{
				list($username,$domain)=explode('@',$email);
				if(!checkdnsrr($domain,'MX')) 
				{
					return false;
				}
				return true;
			}
			return false;
		}
				
		static function tractaParametres ($params, $p_instid, $p_lang, $p_searchflag = 0, $extra = array()) {
			$def_params = array();
			$p_temp1=explode('|',$params);
			while ($valor = current($p_temp1)) {
				$p_temp2=explode('=',$valor);
				$def_params[trim($p_temp2[0])] = trim($p_temp2[1]);
				next($p_temp1);
			}

			if (isset($def_params['inst_id']) && $def_params['inst_id']=='%inst_id%') $def_params['inst_id']=$p_instid;
			if ($def_params['lang']=='%idioma%') $def_params['lang']=$p_lang;
			$def_params['search_flag'] = $p_searchflag;

			foreach ($extra as $key => $value) $def_params[trim($key)] = trim($value);

			return $def_params;
		}

		//////////////////////////////////////////////////////////////////////////////////////////
		static function random_string($length) {
			 srand((double)microtime() * 1000000);
			 $possible_charactors = "abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			 $string = "";
			while(strlen($string)<$length) {
				$string .= substr($possible_charactors, (rand()%(strlen($possible_charactors))),1);
			 }
			 return($string);
		}

		//////////////////////////////////////////////////////////////////////////////////////////
		static function testSession () {
			$aux=$_SESSION['u_id'];
			if (isset($aux) && $aux != null) return 1;
				else return 0;
		}


		static function retorna_browselang() {
			include "lang_class.php";

			$l = new detect_language;
			$dl = $l->getLanguage();

			return $dl;
		}

		//////////////////////////////////////////////////////////////////////////////////////////
		static function default_idioma () {
			global $array_langs;
			return $array_langs[0];
		}

		static function sanitize($var) {
			$var = strip_tags($var);
			$malo = array("\\","\'","\"","<",">");
			$bueno = array("/","&acute;","&acute;","&lt;","&gt;");
			$i=0;
			$o=count($malo);
			while($i<=$o) {
				$var = str_replace($malo[$i],$bueno[$i],$var);
				$i++;
			}
			return $var;
		}

		static function clean_url($url) {
			$ret=$url;
			if (stripos($ret, '&')!==false) {// tenim &
				if (stripos($ret, '&amp;')===false) {// NO tenim &amps;
					$ret=str_replace('&', '&amp;', $ret);
				}
			}
			return $ret;
		}


		static function clean_title($title) {
			$ret=$title;
			$ret=strip_tags($ret);
			$ret=str_replace('"', "&quot;", $ret);
			return $ret;
		} 

		/**
		 * @param email email to obfuscate (String)
		 * @return String obfuscated email
		**/
		static function obfuscate_email($email){
				$link = '';
				foreach(str_split($email) as $letter)
				$link .= '&#'.ord($letter).';';
				return $link;
		}
		
		
		static function get_headerEM() {
			return '<link rel="stylesheet" type="text/css" href="/css/front-edit.css" />
			<script type="text/javascript" src="/js/front-edit.js"> </script>';
		}

		//////////////////////////////////////////////////////////////////////////////////////////
		static function get_linkEM() {
			return '<a accesskey="z" href="javascript://" onclick="toggleEdit(this);" class="link-edit">Edit mode: OFF</a>';
		}

		//////////////////////////////////////////////////////////////////////////////////////////
		static function get_footerEM() {
			return '<script type="text/javascript">
				toggleStart();
			</script>
			<noscript>editmode</noscript>';
		}
		
		static function checkCookieExist($name) {
			return isset ($_COOKIE[$name]);
		}
}
