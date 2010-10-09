<?php
/**
 * Simple calendar class
 *
 * @description   Show a calendar on a page and work with dates
 * @version       1.1
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
	
	private $supplied_date;

	public static function today_if_null($date = null)
	{
		return is_string($date) ? strtotime($date) : (is_int($date) ? $date : time());
	}

	public static function start_of_month($date = null)
	{
		$time = WMCalendar::today_if_null($date);
		return gmmktime(0, 0, 0, date('m', $time), 1, date('Y', $time));
	}

	public static function end_of_month($date = null)
	{
		$time = WMCalendar::today_if_null($date);
		return gmmktime(0, 0, 0, date('m', $time), date('t', $time), date('Y', $time));
	}

	public static function start_of_week($date = null)
	{
		$time = WMCalendar::today_if_null($date);
		$start = gmmktime(0, 0, 0, date('m', $time), (date('d', $time)+WMCalendar::WEEK_START)-date('w', $time), date('Y', $time));
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
			array_push($output, strftime("%a", $day));
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
			$caption_render = create_function('$date', 'return \'Week \'.date(\'W\', $date);');
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
			$class = 'd'.date('j', $day).' m'.date('n', $day).' y'.date('Y', $day).' w'.date('W', $day);
			if(date('j n Y', time()) == date('j n Y', $day)) $class.= ' today';
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
			$day_render = create_function('$day', 'return date(\'d\', $day);');
		}
		if($caption_render == null)
		{
			$caption_render = create_function('$date', 'return strftime(\'%B %Y\', $date);');
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
			$class = 'd'.date('j', $day).' m'.date('n', $day).' y'.date('Y', $day).' w'.date('W', $day);

			if($day < $start_of_month) $class.= ' lastmonth';
			else if($day > $end_of_month) $class.=' nextmonth';
			else $class.= ' thismonth';
			if(date('j n Y', time()) == date('j n Y', $day)) $class.= ' today';
			if($day_counter % 7 == 1)
			{
				$output.= '<tr>';
			}
			$output.= '<td class="'.$class.'">'.call_user_func($day_render, $day).'</td>';
			if($day_counter % 7 == 0)
			{
				$output.= '</tr>';
			}
			$day = $day + WMCalendar::DAY;
			$day_counter++;
		}

		$output.= '</tbody></table>';

		return $output;
	}
}