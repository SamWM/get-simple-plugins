#Get-Simple-Plugin Events

##File Meta Info (file-info/file-info.php)

* Store meta information about files (title, tooltips, tags etc), go to admin section 'files' to change.

* List files (tagged with the current pages 'slug') with:

	<?php echo file_meta_list('tags', return_page_slug()) ?>

* Or just tagged 'leaflet':

	<?php echo file_meta_list('tags', 'leaflet') ?>


##Events (events/events.php)

* Adds events sidebar menu in admin section 'pages' to allow adding events.

* Show events on page by editing the template and adding the following:

	<?php echo events_list() ?>

* Show the calendar (for navigation):

	<?php echo events_calendar() ?>

* Show upcoming events (e.g. in the sidebar):

	<?php echo '<h2>Upcoming events</h2><div class="feature">'.upcoming_events($SITEURL.'events/', 'strong').'</div>' ?>

* `upcoming_events` has three arguments: $base_url, $date_heading_tag, $limit
$base_url is the page the the links are on. $date_heading_tag is the tag to wrap the
date in. $limit is how many events to show (default is set to three)

##License	
All code is licensed under a BSD style license and requires PHP 5.1 or later, unless noted otherwise.
[License](https://github.com/dougrdotnet/get-simple-plugins/blob/master/LICENSE)