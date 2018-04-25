<?php

namespace Omatech\Editora\Loader;


class MultiGeoCoder {

		var $enabled_hosts=array();
		var $status='pending'; // pending, ok, error
		var $debug_str='';
		var $err_arr=array();
		var $raw_response='';
		var $raw_json=null;
		var $results=array();
		
		function __construct($available_hosts) 
		{
				foreach ($available_hosts as $host)
				{
						$this->debug("Testing available host $host\n");
						$ret=@file_get_contents($host.'test');
						if ($ret=='OK')
						{
								array_push($this->enabled_hosts, $host);
								$this->debug("OK! $host\n");
						}
						else
						{
								$this->debug("NOTFOUND $host\n");								
						}
				}
		
				$this->debug("Available hosts after init\n");
				$this->debug(print_r($this->enabled_hosts, true));
		}
		
		function get_random_host()
		{
				return $this->enabled_hosts[array_rand($this->enabled_hosts)];
		}
		
		function debug($str)
		{
				$this->debug_str.=$str;
		}
		
		function geocode ($geostr)
		{
			$this->status='pending';
			$this->debug='';
			$this->err_arr=array();
			$raw_response='';
		  $raw_json=null;
		  $results=array();
			
			if (empty($this->enabled_hosts))
			{
					$this->debug("No podemos geolocalizar sin hosts!!!\n");
					$this->status="error";
			}
			$host=$this->get_random_host();
			$url=$host.urlencode($geostr);
			$this->debug("Tractant geostr: $geostr amb el host $host\n");

			$provincia_id=$viewport=$bounds=$lng=$lat=$localidad=$poblacion=$provincia=$comunidad=$country=$formatted_address=$cp='';

			$body=@file_get_contents($url);
			$json=json_decode($body, true);
			//print_r($json);


			if (isset($json['results'][0]))
			{
				$this->raw_response=$body;
				$this->raw_json=$json;
				foreach($json['results'][0]['address_components'] as $val)
				{
					$types=$val['types'];
					//print_r($types);
					if (in_array('political', $types))
					{
						if (in_array('country', $types))
						{
							$country=$val['long_name'];
						}
						if (in_array('locality', $types))
						{
							$localidad=$val['long_name'];
						}
						if (in_array('administrative_area_level_2', $types))
						{
							$provincia=$val['long_name'];
						}
						if (in_array('administrative_area_level_1', $types))
						{
							$comunidad=$val['long_name'];
						}
						if (count($types)==1)
						{
							$poblacion=$val['long_name'];
						}
					}
					else
					{
						if (in_array('postal_code', $types))
						{
							$cp=$val['long_name'];
						}

					}

				}

				if ($poblacion=='') $poblacion=$localidad;
				if ($localidad=='') $localidad=$poblacion;

				$formatted_address=$json['results'][0]['formatted_address'];
				$lat=$json['results'][0]['geometry']['location']['lat'];
				$lng=$json['results'][0]['geometry']['location']['lng'];
				//$bounds=serialize($json['results'][0]['geometry']['bounds']);
				$viewport=serialize($json['results'][0]['geometry']['viewport']);
				//$provincia_id=$row['provincia_id'];
				
				$this->results['lat']=$lat;
				$this->results['lng']=$lng;
				$this->results['localidad']=$localidad;
				$this->results['poblacion']=$poblacion;
				$this->results['cp']=$cp;
				$this->results['provincia']=$provincia;
				$this->results['comunidad']=$comunidad;
				$this->results['country']=$country;
				$this->results['formatted_address']=$formatted_address;
				$this->results['bounds']=$bounds;
				$this->results['viewport']=$viewport;

				$this->debug("Lat Lng: ".$lat.' '.$lng.' OK'."\n");
				$this->debug('Localidad: '.$localidad."\n");
				$this->debug('Poblacion: '.$poblacion."\n");
				$this->debug('CP: '.$cp."\n");
				$this->debug('Provincia: '.$provincia."\n");
				$this->debug('Comunidad: '.$comunidad."\n");
				$this->debug('Country: '.$country."\n");		
				$this->debug('Formatted Address: '.$formatted_address."\n");
				$this->debug('lat: '.$lat."\n");
				$this->debug('lng: '.$lng."\n");
				$this->debug('bounds: '.$bounds."\n");
				$this->debug('viewport: '.$viewport."\n");
				$this->debug("\n");	
				$this->status="ok";
			}
			else
			{
				$this->status="error";
				
				if (strpos($body, 'OVER_QUERY_LIMIT')===false)
				{
					$this->debug("ERROR INESPERADO!\n");
				}
				$row['response']=$body;
				array_push($this->err_arr, $row);
				$this->debug($body);		
			}

			return $this->results;					
		}		
}
