<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Omatech\Editora\Utils;


class Ips {
		
		static function get_real_ip() {
			if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '') {
				$client_ip =
				(!empty($_SERVER['REMOTE_ADDR'])) ?
					$_SERVER['REMOTE_ADDR']
					:
					((!empty($_ENV['REMOTE_ADDR'])) ?
						$_ENV['REMOTE_ADDR']
						:
						"unknown"
					);

				// los proxys van a�adiendo al final de esta cabecera
				// las direcciones ip que van "ocultando". Para localizar la ip real
				// del usuario se comienza a mirar por el principio hasta encontrar
				// una direcci�n ip que no sea del rango privado. En caso de no
				// encontrarse ninguna se toma como valor el REMOTE_ADDR
				$entries = split('[, ]', $_SERVER['HTTP_X_FORWARDED_FOR']);

				reset($entries);
				while (list(, $entry) = each($entries)) {
					$entry = trim($entry);
					if (preg_match("/^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/", $entry, $ip_list)) {
						// http://www.faqs.org/rfcs/rfc1918.html
						$private_ip = array(
							'/^0\./',
							'/^127\.0\.0\.1/',
							'/^192\.168\..*/',
							'/^172\.((1[6-9])|(2[0-9])|(3[0-1]))\..*/',
							'/^10\..*/'
						);

						$found_ip = preg_replace($private_ip, $client_ip, $ip_list[1]);

						if ($client_ip != $found_ip) {
							$client_ip = $found_ip;
							break;
						}
					}
				}
			}
			else {
				$client_ip =
				(!empty($_SERVER['REMOTE_ADDR'])) ?
					$_SERVER['REMOTE_ADDR']
								:
								((!empty($_ENV['REMOTE_ADDR'])) ?
									 $_ENV['REMOTE_ADDR']
									 :
									 "unknown"
					);
			}

			return $client_ip;
		}		

		static function ip_is_valid($ip)
		{
				return(!preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $ip)) ? false : true;
		}
		
		
}
