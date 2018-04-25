<?php

namespace Omatech\Editora\Utils;

class Urls {

		static function extract_url_info() 
		{
			global $debug;
			
			$url = $_SERVER['REQUEST_URI'];
			$l_step1 = explode('&', $url);
			$l_step2 = explode('?', $l_step1[0]);
			$laurl = explode ('/', $l_step2[0]);

			/* LANGUAGE */
			////////////////////////////////
			if(\Omatech\Editora\Utils\Editora::comproba_idioma($laurl[1])) {
				$lg = $laurl[1];
				$_SESSION['language']=$lg;
			}
			else {
				if (isset($laurl[1]) && $laurl[1]=='') { //llavors estem a la Home
					if (isset($_SESSION['language']) && \Omatech\Editora\Utils\Editora::comproba_idioma($_SESSION['language'])) $lg=$_SESSION['language'];
					else $lg=\Omatech\Editora\Utils\Strings::default_idioma();
					$laurl[1]=$lg;
				}
				else {
					if (isset($_SESSION['language']) && \Omatech\Editora\Utils\Editora::comproba_idioma($_SESSION['language'])) $lg=$_SESSION['language'];
					else $lg=\Omatech\Editora\Utils\Strings::default_idioma();
					$objecte=$laurl[1];
					$laurl[2]=$objecte;
					$laurl[1]=$lg;
				}
			}

			/* INSTANCE*/
			////////////////////////////////
			if (isset($laurl[2]) && $laurl[2]!='') {
				$objecte = $laurl[2];
			}
			else {
				$objecte='home';
				$laurl[2]=$objecte;
			}

			/* OBJECT */
			if(!isset($laurl[5]) && !\Omatech\Editora\Utils\Editora::control_objecte($objecte, $lg)) 
			{// 1->idioma, 2->url_maca de la instancia, 3->paginacio, 4->format, 5->accio
				if (isset($_SESSION['language']) && \Omatech\Editora\Utils\Editora::comproba_idioma($_SESSION['language'])) $lg=$_SESSION['language'];
				else $laurl[1]=\Omatech\Editora\Utils\Strings::default_idioma();
				$laurl[2]='error';
				$laurl[5]='error';
			}
			if(!isset($laurl[5]) || !\Omatech\Editora\Utils\Editora::control_classe(trim($laurl[5]))) 
			{// 1->idioma, 2->url_maca de la instancia, 3->paginacio, 4->format, 5->accio
				$laurl[5]=\Omatech\Editora\Utils\Editora::default_object_accio($objecte, $lg);
			}

			/* PAGINATION */
			if (\Omatech\Editora\Utils\Strings::not_empty($_REQUEST['page'])) $pag_num = trim($_REQUEST['page']);
			if (!\Omatech\Editora\Utils\Strings::not_empty($pag_num) || is_nan($pag_num)) $pag_num = 1;
			$_SESSION['pagina_paginacio'] = $pag_num;

			//Mapping
			$laurl_ultimate['page'] = $pag_num;
			$laurl_ultimate['language'] = $laurl[1];
			$laurl_ultimate['action'] = $laurl[2];
			$laurl_ultimate['param1'] = (isset($laurl[3]) && $laurl[3])?$laurl[3]:'';
			$laurl_ultimate['param2'] = (isset($laurl[4]) && $laurl[4])?$laurl[4]:'';
			$laurl_ultimate['class'] = $laurl[5];
			$_REQUEST['language'] = $lg;
			$_REQUEST['action'] = $laurl[2];
			$_REQUEST['param1'] = (isset($laurl[3]) && $laurl[3])?$laurl[3]:'';
			$_REQUEST['param2'] = (isset($laurl[4]) && $laurl[4])?$laurl[4]:'';
			$_REQUEST['class'] = $laurl[5];

			if ($debug)
			{
			  $debug->debug('LAURL:\n'.print_r($laurl_ultimate, true));
			}
			return $laurl_ultimate;
		}

		//////////////////////////////////////////////////////////////////////////////////////////
		static function permanent_redirect($url) {
			// Permanent redirection
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: $url");
			die();
		}

		//////////////////////////////////////////////////////////////////////////////////////////
		static function notfound(){
			header("HTTP/1.0 404 Not Found");
			header('Content-Type: text/html; charset=UTF-8', true);
			require_once(DOCUMENT_ROOT.'/actions/notfound.php');
		}

}
