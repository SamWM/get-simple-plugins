<?php
/**
 * Events
 *
 * @description   Simple event management
 * @version       2.1f
 * @author        Sam Collett
 * @license       http://github.com/SamWM/get-simple-plugins/blob/master/LICENSE
 */

# get correct id for plugin
$thisfile=basename(__FILE__, ".php");

# register plugin
register_plugin(
	$thisfile, 
	'Events',
	'2.1f',
	'Sam Collett',
	'http://www.texotela.co.uk', 
	'Manage Events',
	'pages',
	'events_form'  
);

# activate filter
add_action('index-pretemplate', 'events_preload');
add_action('header', 'events_header');
add_action('pages-sidebar','createSideMenu',array($thisfile,'Events')); 

// previous, next month link text. leave blank '' to use shortened month name
$events_previous_month_text = ''; // e.g. 'prev &lt;&lt;'		
$events_next_month_text = ''; // e.g. '&lt;&lt; next'	

# path to xml file
$events_path = GSDATAOTHERPATH.'events.xml';
# xml data
$events_xml;
$events_base_url;
$events_calendar_date;
# class
require_once 'php-helper/calendar.php';

# functions
function events_preload()
{
	global $events_base_url, $events_xml, $events_path, $events_calendar_date;
	// if 'id=' is not in the request uri, then clean url's is off, or the user is in the admin panel
	if(stripos($_SERVER["REQUEST_URI"], 'id=') === false)
	{
		$req = explode('?', $_SERVER["REQUEST_URI"]);
		$events_base_url = $req[0];
	}
	else
	{
		$events_base_url = htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES).'?id='.$_GET['id'];
	}
	if(stripos($events_base_url, '?') === false)
	{
		$events_base_url .= "?";
	}
	else
	{
		$events_base_url .= "&";
	}
	if(is_file($events_path)) {
		$events_xml = getXML($events_path);
	}
	else
	{
		$events_xml = @new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><events></events>');
		if(function_exists("XMLsave"))
		{
			XMLsave($events_xml, $events_path);
		}
		else
		{
			$events_xml->asXML($events_path);
		}
	}
	// set events_calendar_date
	if(!empty($_GET['month']))
	{
		$qs_month = $_GET['month'];
	}
	if(!empty($_GET['year']))
	{
		$qs_year = $_GET['year'];
	}
	if(!empty($_GET['day']))
	{
		$qs_day = $_GET['day'];
	}
	
	if(isset($qs_month))
	{
		if(!isset($qs_day)) $qs_day = 1;
		if(!isset($qs_year)) $qs_year = date('Y');
		$events_calendar_date = WMCalendar::today_if_null($qs_year.'-'.$qs_month.'-'.$qs_day);
	}
	else
	{
		$events_calendar_date = WMCalendar::today_if_null();
	}
	// set to midnight
	$events_calendar_date = gmmktime(0, 0, 0, date('n', $events_calendar_date), date('j', $events_calendar_date), date('Y', $events_calendar_date));
	// END set events_calendar_date
	
	if (isset($_REQUEST["event_action"]))
	{
		events_manage($_REQUEST["event_action"]);
	}
}

function events_header()
{
	global $EDTOOL, $EDLANG, $EDHEIGHT, $SITEURL;
	if (defined('GSEDITORHEIGHT')) { $EDHEIGHT = GSEDITORHEIGHT .'px'; } else {	$EDHEIGHT = '500px'; }
	if (defined('GSEDITORLANG')) { $EDLANG = GSEDITORLANG; } else {	$EDLANG = 'en'; }
	if (defined('GSEDITORTOOL')) { $EDTOOL = GSEDITORTOOL; } else {	$EDTOOL = 'basic'; }
	
	
	if ($EDTOOL == 'advanced') {
		$toolbar = "
				['Bold', 'Italic', 'Underline', 'NumberedList', 'BulletedList', 'JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock', 'Table', 'TextColor', 'BGColor', 'Link', 'Unlink', 'Image', 'RemoveFormat', 'Source'],
	  '/',
	  ['Styles','Format','Font','FontSize']
  ";
	} elseif ($EDTOOL == 'basic') {
		$toolbar = "['Bold', 'Italic', 'Underline', 'NumberedList', 'BulletedList', 'JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock', 'Link', 'Unlink', 'Image', 'RemoveFormat', 'Source']";
	} else {
		$toolbar = GSEDITORTOOL;
	}
	$js = <<<JS
	<script type="text/javascript" src="template/js/ckeditor/ckeditor.js"></script>
	<script type="text/javascript" src="template/js/ckeditor/adapters/jquery.js"></script>
	<script type="text/javascript">
	var editor;
	$(
		function() {
			$("input.delete").click(
				function(e) {
					if(!confirm('Are you sure you wish to delete this event?'))
					{
						e.preventDefault();
					}
				}
			);
			editor = $("textarea[name=event_content]").ckeditor(function(){},
				{
					skin : 'getsimple',
					forcePasteAsPlainText : true,
					language : '$EDLANG',
					defaultLanguage : '$EDLANG',
					entities : true,
					uiColor : '#FFFFFF',
					height: '$EDHEIGHT',
					baseHref : '$SITEURL',
					toolbar : [$toolbar]
				}
			).ckeditorGet();
		}
	)
	</script>
JS;
	// preload data
	events_preload();
	echo <<<STYLE
	<style type="text/css">
	span.field {
		line-height: 24px;
		margin-left: 5px;
	}
	
	input.delete
	{
		color: #f00;
	}
	
	table.calendar tbody td
	{
		width: 14%;
	}
	
	table.calendar tbody td span.event_info
	{
		font-size: 0.875em;
	}
	
	table.calendar tbody td span.no_events
	{
		color: #eee;
	}
	
	table.calendar tbody td.lastmonth,
	table.calendar tbody td.nextmonth
	{
		background: #eee;
	}
	
	table.calendar tbody td.today
	{
		background: #fcfcfc;
	}
	
	table.calendar tbody td div.selected {
		border: 1px solid #000;
		width: 100%;
		height: 100%;
	}
	
	label.clear {
		float: none;
	}
	</style>
	$js
STYLE;
}

function pad_number(&$item, $key, $pad = 2)
{
	$item = sprintf('%0'.$pad.'d', $item);
}

function build_time_options($hours, $minutes, $time)
{
	// pad hours and minutes with 0
	array_walk($hours, 'pad_number');
	array_walk($minutes, 'pad_number');
	$time_hour_options = '';
	$time_minute_options = '';
	
	$time_components = explode(':', $time);

	foreach($hours as $hour)
	{
		if(count($time_components) == 2)
		{
			if($time_components[0] == $hour)
			{
				$time_hour_options .= '<option selected="selected">'.$hour.'</option>';
			}
			else
			{
				$time_hour_options .= '<option>'.$hour.'</option>';
			}
		}
		else
		{
			$time_hour_options .= '<option>'.$hour.'</option>';
		}
	}
	foreach($minutes as $minute)
	{
		if(count($time_components) == 2)
		{
			if($time_components[1] == $minute)
			{
				$time_minute_options .= '<option selected="selected">'.$minute.'</option>';
			}
			else
			{
				$time_minute_options .= '<option>'.$minute.'</option>';
			}
		}
		else
		{
			$time_minute_options .= '<option>'.$minute.'</option>';
		}
	}
	
	return array('hours' => $time_hour_options, 'minutes' => $time_minute_options);
}

function events_form()
{
	global $events_base_url, $events_xml, $events_calendar_date;
	// get events
	$events = $events_xml->xpath('//events/event');
	// get count of events
	$eventcount = count($events);
	// new event id
	$new_event_id = $eventcount + 1;
	// if loading existing event
	if(!empty($_GET['event_id']))
	{
		$event_id = $_GET['event_id'];
		$new_event = false;
	}
	else
	{
		$event_id = $new_event_id;
		$new_event = true;
	}
	
	$event_title = '';
	$event_date = '';
	$event_start_time = '';
	$event_end_time = '';
	$event_location = '';
	$event_content = '';
	// find the event in the XML document
	$event_item = $events_xml->xpath('//events/event[@event_id='.$event_id.']');
	// if the event is found
	if(count($event_item) > 0) {
		$event_title = $event_item[0]['event_title'];
		$event_date = $event_item[0]['event_date'];
		$event_start_time = $event_item[0]['event_start_time'];
		$event_end_time = $event_item[0]['event_end_time'];
		$event_location = $event_item[0]['event_location'];
		$current_content = $event_item[0]->xpath('content');
		if(count($current_content > 0))
		{
			$event_content = $current_content[0];
		}
		
		$events_calendar_date = (int)$event_date;
		echo '<p><a href="'.$events_base_url.'month='.date('n', $events_calendar_date).'&year='.date('Y', $events_calendar_date).'&day='.date('j', $events_calendar_date).'">Cancel Edit</a></p>';
	}
	
	$today = time();
	// display as '<day> <month name> <year>'
	$events_calendar_date_display = date('j F Y', $events_calendar_date);
	// store as number
	$events_calendar_date_store =  $events_calendar_date;
	$calendar = new WMCalendar($events_calendar_date);

	$formaction = $events_base_url.'month='.date('n', $events_calendar_date).'&year='.date('Y', $events_calendar_date).'&day='.date('j', $events_calendar_date);
	$openform = '<form method="post" action="'.$formaction.'">';
	if($new_event)
	{
		$h2_text = "Add Event";
	}
	else
	{
		$h2_text = "Edit Event";
	}
	
	$savebutton = '<input type="submit" name="event_action" value="Save" class="submit" />';
	$deletebutton = '<input type="submit" name="event_action" value="Delete" class="submit delete" />';
	
	$hours = array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23);
	$minutes = array(0,5,10,15,20,25,30,35,40,45,50,55);
	
	$start_time_options = build_time_options($hours, $minutes, $event_start_time);
	$start_time_hour_options = $start_time_options['hours'];
	$start_time_minute_options = $start_time_options['minutes'];
	
	$end_time_options = build_time_options($hours, $minutes, $event_end_time);
	$end_time_hour_options = $end_time_options['hours'];
	$end_time_minute_options = $end_time_options['minutes'];
	
	if($new_event) $deletebutton = '';
	$form = <<<FORM
	$openform
		<h2>$h2_text</h2>
		<p>
			<label>Title: </label>
			<span class="field">
				<input name="event_title" value="$event_title" />
			</span>
		</p>
		<p>
			<label>Date: </label>
			<span class="field">
				$events_calendar_date_display
				<input name="event_date" class="date" type="hidden" value="$events_calendar_date_store" />
			</span>
		</p>
		<p>
			<label>Start Time: </label>
			<span class="field">
				<select name="event_start_time_hour">
					$start_time_hour_options
				</select>
				<select name="event_start_time_minute">
					$start_time_minute_options
				</select>
			</span>
		</p>
		<p>
			<label>End Time: </label>
			<span class="field">
				<select name="event_end_time_hour">
					$end_time_hour_options
				</select>
				<select name="event_end_time_minute">
					$end_time_minute_options
				</select>
			</span>
		</p>
		<p>
			<label>Location: </label>
			<span class="field">
				<input name="event_location" value="$event_location" />
			</span>
		</p>
		<label class="clear">Content: </label>
		<textarea name="event_content" id="post-content">$event_content</textarea>
		<p>
			<input type="hidden" name="event_id" value="$event_id" />
			$savebutton
			$deletebutton
		</p>
	</form>
FORM;

	if($new_event)
	{
		if(date('j n Y', $today) != date('j n Y', $events_calendar_date))
		{
			echo '<p>Go to <a href="'.$events_base_url.'today">Today</a> ('.strftime('%#d %B %Y', $today).')</p>';
		}
		echo $calendar->render('event_day_render', 'event_caption_render');
		echo $form;
		
		// convert $events_calendar_date to string (minus time), then back to int to get the numerical representation of the date for midnight on the same day
		$current_events = $events_xml->xpath('//events/event[@event_date='.strtotime(date('Y-m-d', $events_calendar_date)).']');
		// old method (only works in English locale?)
		//$current_events = $events_xml->xpath('//events/event[@event_date='.strtotime(date('j F Y', $events_calendar_date)).']');
		$event_count = count($current_events);
		if($event_count > 0)
		{
			events_sidebar($current_events);
		}
	}
	else
	{
		echo $form;
		if(date('j n Y', $today) != date('j n Y', $events_calendar_date))
		{
			echo '<p>Go to <a href="'.$events_base_url.'">Today</a> ('.strftime('%#d %B %Y', $today).')</p>';
		}
		echo $calendar->render('event_day_render', 'event_caption_render');
	}
}

function event_day_render($day)
{
	global $events_base_url, $events_xml, $events_calendar_date;
	if(!empty($_GET['event_id']))
	{
		$selected_event = $events_xml->xpath('//events/event[@event_id='.$_GET['event_id'].']');
		if(count($selected_event) > 0)
		{
			$event_date = (int)$selected_event[0]['event_date'];
			$qs_day = date('j', $event_date);
			$qs_month = date('n', $event_date);
			$qs_year = date('Y', $event_date);
		}
	}
	$output = date('d', $day);
	$link = '<a href="'.$events_base_url.'month='.date('n', $day).'&year='.date('Y', $day).'&day='.date('j', $day).'">'.$output.'</a>';
	
	$events = $events_xml->xpath('//events/event[@event_date='.strtotime(date('Y-m-d', $day)).']');
	$event_count = count($events);
	$event_info = '';
	if( stripos($events_base_url, 'admin') !== false)
	{
		$event_info = '<br/><span class="event_info'.($event_count == 0 ? ' no_events' : '').'">('.$event_count.' event'.($event_count != 1 ? 's':'').')</span>';
	}
	else
	{
		// if no events, no link
		if($event_count == 0)
		{
			$link = $output;
		}
	}
	
	if(!empty($_GET['day']))
	{
		$qs_day = $_GET['day'];
	}
	
	if(isset($qs_day))
	{
		if(date('j n Y', $day) == date('j n Y', $events_calendar_date))
		{
			$output = '<div class="selected">'.$output.$event_info.'</div>';
		}
		else
		{
			$output = $link.$event_info;
		}
	}
	else if(date('j n Y', $day) == date('j n Y', time()) && empty($_GET['month']))
	{
		$output = '<div class="selected">'.$output.$event_info.'</div>';
	}
	else
	{
		$output = $link.$event_info;
	}
	
	return $output;
}

function event_caption_render($event_date)
{
	global $events_base_url, $events_previous_month_text, $events_next_month_text;
	
	// text after the previous month link
	$previous_month_suffix = '';
	
	$previous_month = WMCalendar::start_of_month($event_date) - WMCalendar::DAY;
	
	if(strlen($events_previous_month_text) == 0)
	{
		$events_previous_month_text = strftime('%B %Y', $previous_month);
		$previous_month_suffix = ' &laquo; ';
	}
	
	$previous_month_link = '<a href="'.$events_base_url.'month='.date('n', $previous_month).'&year='.date('Y', $previous_month).'">'.$events_previous_month_text.'</a>'.$previous_month_suffix;
	
	// text before the next month link
	$next_month_prefix = '';
	
	$next_month = WMCalendar::end_of_month($event_date) + WMCalendar::DAY + 1;
	
	if(strlen($events_next_month_text) == 0)
	{
		$events_next_month_text = strftime('%B %Y', $next_month);
		$next_month_prefix = ' &raquo; ';
	}
	
	$next_month_link = $next_month_prefix.'<a href="'.$events_base_url.'month='.date('n', $next_month).'&year='.date('Y', $next_month).'">'.$events_next_month_text.'</a>';
	
	return utf8_encode('<span class="previousmonth">'.$previous_month_link.'</span> <span class="currentmonth">'.strftime('%B %Y', $event_date).'</span> <span class="nextmonth">'.$next_month_link.'</span>');
}

function events_sidebar($events = null)
{
	global $events_base_url, $events_xml;
	if($events == null)
	{
		$events = $events_xml->xpath('//events/event[@event_date='.strtotime(date('Y-m-d', time())).']');
	}
	$event_count = count($events);
	if($event_count > 0)
	{
		foreach($events as $event)
		{
			add_action('pages-sidebar','createSideMenu',array('events&event_id='.$event['event_id'], 'Event: '.$event['event_title'])); 	
		}
	}
}

function upcoming_events($base_url, $date_heading_tag, $limit = 3)
{
	global $events_xml;
	$events = $events_xml->xpath('//events/event[@event_date>'.strtotime(date('Y-m-d', time())).'][position()<='.$limit.']');
	return events_list($events, $base_url, $date_heading_tag);
}

function events_list($events = null, $base_url = null, $date_heading_tag = 'h2')
{
	global $events_base_url, $events_xml, $events_calendar_date;
	if($base_url == null) $base_url = $events_base_url;
	if(!stripos($base_url, '?')) $base_url .= '?';
	if($events === null)
	{
		if(isset($_GET['event_id']))
		{
			$events = $events_xml->xpath('//events/event[@event_id='.$_GET['event_id'].']');
			if(count($events) > 0)
			{
				$events_calendar_date = (int)$events[0]['event_date'];
			}
		}
		if(count($events) == 0)
		{
			if(isset($_GET['day']))
			{
				// current calendar date events
				$events = $events_xml->xpath('//events/event[@event_date='.strtotime(date('Y-m-d', $events_calendar_date)).']');
			}
			else
			{
				// this months events
				$month_start = strtotime(date('Y-m-d', WMCalendar::start_of_month($events_calendar_date)));
				$month_end = strtotime(date('Y-m-d', WMCalendar::end_of_month($events_calendar_date)));
				$events = $events_xml->xpath('//events/event[@event_date>='.$month_start.' and @event_date<'.$month_end.']');
			}
		}
	}
	$output = '';
	$event_count = count($events);
	if($event_count > 0)
	{
		usort($events, 'events_sort_date');
		$date_headers = array();
		$list = '';
		foreach($events as $event)
		{
			$event_date = (int)$event['event_date'];
			$date_formatted = strftime('%#d %B %Y', $event_date);
			if(!in_array($date_formatted, $date_headers))
			{
				array_push($date_headers, $date_formatted);
				if($list != '') $list .= '</ul>';
				if(empty($_GET['day']))
				{
					$list .= '<'.$date_heading_tag .'>'.$date_formatted.'</'.$date_heading_tag .'>';
				}
				else
				{
					$list .= '<'.$date_heading_tag .'><a href="'.$base_url.'month='.date('n', $event_date).'&year='.date('Y', $event_date).'">'.strftime('%B %Y', $event_date).'</a> &raquo; '.$date_formatted.'</'.$date_heading_tag.'>';
				}
				if(!empty($_GET['day']))
				{
					$list .= '<ul class="events_list">';
				}
				else
				{
					$list .= '<ul class="events_list_brief">';
				}
			}
			$list .= '<li id="event'.$event['event_id'].'">';
			$content = '';
			// show content if viewing by day
			if(!empty($_GET['day']))
			{
				$current_content = $event->xpath('content');
				if(count($current_content) > 0)
				{
					$content = '<br /><span class="event_details">'.$current_content[0][0].'</span>';
				}
				$event_title = $event['event_title'];
			}
			else
			{
				// make event title a link
				$event_title = '<a href="'.$base_url.'month='.date('n', $event_date).'&year='.date('Y', $event_date).'&day='.date('j', $event_date).'#event'.$event['event_id'].'">'.$event['event_title'].'</a>';
			}
			
			$times = 'All day';
			if(	!empty($event['event_start_time']) && !empty($event['event_end_time'])
				&& ($event['event_start_time'] != '00:00' && $event['event_end_time'] != '00:00'))
			{
				$times = '<span class="starttime">'.$event['event_start_time'].'</span> to <span class="endtime">'.$event['event_end_time'].'</span>';
			}
			$event_location = '';
			if(	!empty($event['event_location']) )
			{
				$event_location = '<span class="location">'.$event['event_location'].'</span>';
			}
			$list .= <<<EVENT
			<span class="event_title">$event_title</span>
			<span class="times">$times</span>
			$event_location
			$content
EVENT;
			
			
			$list .='</li>';	
		}
		$list .= '</ul>';
		
		$output .= $list;
	}
	else
	{
		$output = 'No events '.(isset($_GET['day']) ? ' on '.date('j F Y', $events_calendar_date) : ' in '.date('F Y', $events_calendar_date));
	}
	
	return $output;
}

function events_calendar()
{
	global $events_calendar_date;

	$calendar = new WMCalendar($events_calendar_date);
	$output = $calendar->render('event_day_render', 'event_caption_render');
	
	return $output;
}

function events_sort_date($a, $b)
{
	// if same date, compare times
	if((int)$a['event_date'] == (int)$b['event_date'])
	{
		// earliest time first
		return $a['event_end_time'].$a['event_start_time'] > $b['event_end_time'].$b['event_start_time'];
	}
	else
	{
		// latest date first
		// return (int)$a['event_date'] < (int)$b['event_date'];
		// earliest date first
		return (int)$a['event_date'] > (int)$b['event_date'];
	}
}
function events_manage($event_action)
{
	global $events_xml, $events_path;
	if($event_action == 'Delete')
	{
		if(!empty($_POST['event_id']))
		{
			$event_id = $_POST['event_id'];
			// find the event in the XML document
			$event_item = $events_xml->xpath('//events/event[@event_id="'.$event_id.'"]');
			// if the event is found
			if(count($event_item) > 0) {
				unset($event_item[0][0]);
			}
		}
	}
	else if($event_action == 'Save')
	{
		if(!empty($_POST['event_title']) && !empty($_POST['event_date']))
		{
			$event_id = $_POST['event_id'];
			$event_title = utf8_encode($_POST['event_title']);
			$event_date = $_POST['event_date'];
			$event_start_time = $_POST['event_start_time_hour'].':'.$_POST['event_start_time_minute'];
			$event_end_time = $_POST['event_end_time_hour'].':'.$_POST['event_end_time_minute'];
			$event_location = utf8_encode($_POST['event_location']);
			$event_content = utf8_encode($_POST['event_content']);
			// find the event in the XML document
			$event_item = $events_xml->xpath('//events/event[@event_id="'.$event_id.'"]');
			// if the event is not found
			if(count($event_item) === 0) {
				$event_item = $events_xml->addChild('event');
				$event_item->addAttribute('event_id', $event_id);
			}
			else
			{
				$event_item = $event_item[0];
				// check if there is existing content
				$current_content = $event_item->xpath('content');
				if(count($current_content) > 0)
				{
					// remove existing tag
					unset($current_content[0][0]);
				}
			}
			
			$event_item['event_title'] = $event_title;
			$event_item['event_date'] = $event_date;
			$event_item['event_start_time'] = $event_start_time;
			$event_item['event_end_time'] = $event_end_time;
			$event_item['event_location'] = $event_location;
			
			$content_item = $event_item->addChild('content');
			$content_item->addCData($event_content);
		}
	}
	if(function_exists("XMLsave"))
	{
		XMLsave($events_xml, $events_path);
	}
	else
	{
		$events_xml->asXML($events_path);
	}
}