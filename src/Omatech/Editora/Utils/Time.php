<?php

namespace Omatech\Editora\Utils;

class Time {

		static function time_to_rfc822($newstime)
		{
		//echo '::'.$newstime.'::';
			list($date, $hours) = split(' ', $newstime);
			list($year,$month,$day) = split('-',$date);
			list($hour,$min,$sec) = split(':',$hours);
			//returns the date ready for the rss feed
			$date = date(r,mktime($hour, $min, $sec, $month, $day, $year));
			return str_replace(',  ', ', ', $date);
		}


		static function time_diff($from, $now=0)
		{
			$txt = '';

			if($now==0)
			{
				$now = time();
			}

			$timestamp=strtotime($from);
			$diff=$now-$timestamp;
			$days=intval($diff/86400);
			$diff=$diff%86400;
			$hours=intval($diff/3600);
			$diff=$diff%3600;
			$minutes=intval($diff/60);

			if($days>1)
			{
				$txt .= " ".$days." ".__('TIME_DAYS');
			}
			else if ($days==1) 
			{
				$txt .= " ".$days." ".__('TIME_DAY');
			}

			if($hours>1) 
			{
				$txt .= " ".$hours." ".__('TIME_HOURS');
			}
			else if ($hours==1) 
			{
				$txt .= " ".$hours." ".__('TIME_HOUR');
			}

			if($minutes>1) 
			{
				$txt .= " ".$minutes." ".__('TIME_MINUTES');
			}
			else if ($minutes==1) 
			{
				$txt .= " ".$minutes." ".__('TIME_MINUTE');
			}

			if($txt=='') 
			{
				$txt = " ".__('TIME_FEW');
			}

			return $txt;
		}

		static function spanish_date_to_mysql_date ($spanish_date)
		{// takes an spanish date format dd/mm/yyyy and converts to mysql date format yyyy-mm-dd
				$spanish_date_array=explode('/', $spanish_date);
				return $spanish_date_array[2].'-'.$spanish_date_array[1].'-'.$spanish_date_array[0];
		}

}
