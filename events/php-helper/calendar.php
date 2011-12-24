<?php
/**
 * Simple calendar class
 *
 * @description   Show a calendar on a page and work with dates
 * @version       2.1j
 * @author		  Douglas Reynolds
 * @license		  https://github.com/dougrdotnet/get-simple-plugins/blob/master/LICENSE
 * @author        Sam Collett
 * @license       http://github.com/SamWM/php-helper/blob/master/LICENSE
 */
class WMCalendar
{
	// date contants
	const WEEK = 604800;
	const DAY = 86400;
	const HOUR = 3600;
	const MINUTE = 60;
	// which day does the week start on (0 - 6)
	const WEEK_START = 1;
	// prefix week number with this, e.g. 'Week ' to get 'Week 2'
	const WEEK_PREFIX = 'Week ';
	
	private $supplied_date;

	public static function today_if_null($date = null)
	{
		return is_string($date) ? strtotime($date) : (is_int($date) ? $date : time());
	}

	public static function start_of_month($date = null)
	{
		$time = WMCalendar::today_if_null($date);
		return gmmktime(0, 0, 0, gmdate('n', $time), 1, gmdate('Y', $time));
	}

	public static function end_of_month($date = null)
	{
		$time = WMCalendar::today_if_null($date);
		return gmmktime(0, 0, 0, gmdate('n', $time), gmdate('t', $time), gmdate('Y', $time));
	}

	public static function start_of_week($date = null)
	{
		$time = WMCalendar::today_if_null($date);
		$start = gmmktime(0, 0, 0, gmdate('m', $time), (gmdate('d', $time)+WMCalendar::WEEK_START)-gmdate('w', $time), gmdate('Y', $time));
		if($start > $time) $start -= WMCalendar::WEEK;
		return $start;
	}

	public static function end_of_week($date = null)
	{
		$time = WMCalendar::today_if_null($date);
		return WMCalendar::start_of_week($time) + WMCalendar::WEEK - 1;
	}

	public static function week_days($date = null)
	{
		$time = WMCalendar::today_if_null($date);
		$output = array();

		$startofweek = WMCalendar::start_of_week($date);
		$endofweek = WMCalendar::end_of_week($date);

		$day = $startofweek;

		while( $day < $endofweek )
		{
			array_push($output, gmstrftime("%a", $day));
			$day = $day + WMCalendar::DAY;
		}

		return $output;
	}
	
	public function __construct($date = null)
	{
		$this->supplied_date = WMCalendar::today_if_null($date);
	}

	public function week_render($day_render = null, $caption_render = null)
	{
		if($day_render == null)
		{
			$day_render = create_function('$day', 'return date(\'d\', $day);');
		}
		if($caption_render == null)
		{
			$caption_render = create_function('$date', 'return \''.WMCalendar::WEEK_PREFIX.'\'.gmdate(\'W\', $date);');
		}
		$output = '<table class="WMCalendar"><caption>'.call_user_func($caption_render, $this->supplied_date).'</caption><thead><th>';
		$output.= implode('</th><th>', WMCalendar::week_days($this->supplied_date));
		$output.= '</th></thead><tbody>';

		$firstday = WMCalendar::start_of_week($this->supplied_date);
		$lastday = WMCalendar::end_of_week($this->supplied_date);

		$day = $firstday;
		$output.= '<tr>';

		while($day < $lastday)
		{
			$class = 'd'.gmdate('j', $day).' m'.gmdate('n', $day).' y'.gmdate('Y', $day).' w'.gmdate('W', $day);
			if(gmdate('j n Y', time()) == gmdate('j n Y', $day)) $class.= ' today';
			$output.= '<td class="'.$class.'">'.call_user_func($day_render, $day).'</td>';
			$day = $day + WMCalendar::DAY;
		}
		$output.= '</tr>';

		$output.= '</tbody></table>';

		return $output;
	}

	public function render($day_render = null, $caption_render = null)
	{
		if($day_render == null)
		{
			$day_render = create_function('$day', 'return gmdate(\'d\', $day);');
		}
		if($caption_render == null)
		{
			$caption_render = create_function('$date', 'return gmstrftime(\'%B %Y\', $date);');
		}

		$output = '';
		$output.= '<table class="calendar"><caption>'.call_user_func($caption_render, $this->supplied_date).'</caption><thead><th>';
		$output.= implode('</th><th>', WMCalendar::week_days($this->supplied_date));
		$output.= '</th></thead><tbody>';

		$start_of_month = WMCalendar::start_of_month($this->supplied_date);

		$end_of_month = WMCalendar::end_of_month($this->supplied_date);

		$firstday = WMCalendar::start_of_week($start_of_month);
		$lastday = WMCalendar::end_of_week($end_of_month);
		
		$day = $firstday;
		$class = '';
		$day_counter = 1;
		while($day < $lastday)
		{
			$class = 'd'.gmdate('j', $day).' m'.gmdate('n', $day).' y'.gmdate('Y', $day).' w'.gmdate('W', $day);
			if($day < $start_of_month) $class.= ' lastmonth';
			else if($day > $end_of_month) $class.=' nextmonth';
			else $class.= ' thismonth';
			
			if(gmdate('j n Y', time()) == gmdate('j n Y', $day)) $class.= ' today';
			if($day_counter % 7 == 1 )
			{
				$output.= '<tr>';
			}
			$output.= '<td class="'.$class.'">'.call_user_func($day_render, $day).'</td>';
			if($day_counter % 7 == 0)
			{
				$output.= '</tr>';
			}
			$day = $day + WMCalendar::DAY;
			$day_counter ++;
		}

		$output.= '</tbody></table>';

		return $output;
	}
}