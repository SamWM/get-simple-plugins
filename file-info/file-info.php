<?php
/**
 * File Meta Info
 * 
 * @description   Store additional information about uploaded files. Search and list files matching that meta data
 * @version       1.3.1
 * @author        Sam Collett
 * @license       http://github.com/SamWM/get-simple-plugins/blob/master/LICENSE
 */

# get correct id for plugin
$thisfile=basename(__FILE__, ".php");

# register plugin
register_plugin(
	$thisfile, 
	'File Meta Info', 	
	'1.3.1', 		
	'Sam Collett',
	'http://www.texotela.co.uk', 
	'Save additional meta information for uploaded files. Search and list files matching that meta data.',
	'',
	''  
);

# 'Text to display' => 'metakey' (no spaces)
$file_meta_fields = array('Title' => 'title', 'Tooltip' => 'tooltip', 'Tags' => 'tags');

# activate filter
add_action('index-pretemplate', 'file_meta_preload');
add_action('header', 'file_meta_header');
add_action('file-extras','file_meta_extras');

# path to xml file
$file_meta_path = GSDATAOTHERPATH.'file_meta.xml';
# xml data
$file_meta_xml;

# functions
function file_meta_preload()
{
	global $file_meta_xml, $file_meta_path;
	if(is_file($file_meta_path)) {
		$file_meta_xml = getXML($file_meta_path);
	}
	else {
		$file_meta_xml = @new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><files></files>');
		XMLsave($file_meta_xml, $file_meta_path);
	}
	if (isset($_REQUEST["file_meta"]))
	{
		file_meta_update();
	}
}

function file_meta_header()
{
	// preload data
	file_meta_preload();
	echo '<style type="text/css">
	tr.file_meta { font-size: 0.875em }
	tr.file_meta input.meta { font-size: 1em; width: 100px; border: 1px solid #ddd }
	tr.file_meta td { padding: 0 0 0 18px }
	tr.file_meta .notification { float: right; padding: 1px}
	tr.file_meta .highlight {background-color: #f00; color: #fff}
	tr.file_meta input[name=file_meta] { padding: 2px}
	</style>';
	echo '<script type="text/javascript">
	$( function() {
		$("form.file_meta_form").submit(filemetaSubmit);
	});
	
	function filemetaSubmit(e) {
		e.preventDefault();
		var $this = $(this);
		var $notification = $this.find("div.notification");
		var dataString = $this.serialize() + "&file_meta=Save";
		var method = $this.attr("method").toUpperCase();
		var action = $this.attr("action");
		$.ajax({
			type: method,
			url: action,
			data: dataString,
			success: function(response) {
				$notification.empty().html(response).fadeIn("slow", callback).addClass("highlight");
			}
		});

		function callback() {
			setTimeout(function() {
				$notification.hide("slow").parents("tr.file_meta").removeClass("highlight");
			}, 2000);
		};
	}
	</script>';
}

function file_meta_extras()
{
	global $file_meta_fields, $upload;
	echo '<tr class="file_meta"><td colspan="4">
	<form method="post" action="'.htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES).'" class="file_meta_form">';
	foreach($file_meta_fields as $text => $metakey)
	{
		echo $text.': <input name="meta_'.$metakey.'" value="'.file_meta_data($upload['name'], $metakey).'" class="'.$metakey.' meta" /> ';
	}
	echo '<input type="hidden" name="file" value="'.$upload['name'].'" />
	<input type="submit" value="Save" name="file_meta" /><div class="notification"></span>
	</form>
	</td></tr>';
}

function file_meta_search($meta, $search)
{
	global $file_meta_xml;
	if(is_object($file_meta_xml))
	{
		return $file_meta_xml->xpath('//files/file[contains('.$meta.',"'.$search.'")]');
	}
	return null;
}

function sort_name($a, $b)
{
	return strcmp($a['name'], $b['name']);   
}

function sort_title($a, $b)
{
	$a_val = $a['name'];
	$b_val = $b['name'];
	// use $a title, if it exists
	$a_title = $a->xpath('title');
	if(count($a_title) > 0 && strlen($a_title[0]) > 0)
	{
		$a_val = $a_title[0];
	}
	// use $b title, if it exists
	$b_title = $b->xpath('title');
	if(count($b_title) > 0 && strlen($b_title[0]) > 0)
	{
		$b_val = $b_title[0];
	}
	// compare the two values
	return strcmp($a_val, $b_val);   
}

// list files
function file_meta_list($meta, $search)
{
	global $SITEURL;
	$files = file_meta_search($meta, $search);
	if(count($files) > 0)
	{
		usort($files, "sort_title"); 
		$list = '<ul class="file_list">';
		foreach($files as $file)
		{
			// get file statistics
			$ss = @stat(GSDATAUPLOADPATH.$file['name']);
			// date last modified
			$date = @date('M j, Y',$ss['mtime']);
			// file size
			$size = fSize($ss['size']);
			// file extension
			$ext = strtolower(substr($file['name'], strrpos($file['name'], '.') + 1));
			// if title set in the meta data, use that
			$filetitle = $file->xpath('title');
			if(count($filetitle) > 0 && strlen($filetitle[0]) > 0)
			{
				$title = $filetitle[0];
			}
			// otherwise use file name without the extension
			else $title = substr($file['name'], 0, strrpos($file['name'], '.'));
			$title = htmlspecialchars($title);
			// tooltip
			$tooltip = '';
			// if tooltip is set
			$filetooltip = $file->xpath('tooltip');
			if(count($filetooltip) > 0 && strlen($filetooltip[0]) > 0)
			{
				$tooltip = ' title="'.$filetooltip[0].'"';
			}
			$list .= '<li class="'.$ext.'"><a href="'.$SITEURL.'data/uploads/'.rawurlencode($file['name']).'"'.$tooltip.'>'.$title.'</a> <span class="properties">('.$size.'. '.$date.')</span></li>'; 
		}
		$list .= '</ul>';
		return $list;
	}
	return null;
}

function file_meta_data($file, $meta)
{
	global $file_meta_xml;
	$meta_data = '';
	if(is_object($file_meta_xml))
	{
		$current = $file_meta_xml->xpath('//files/file[@name="'.$file.'"]');
		if(count($current) !== 0) {
			$children = $current[0]->xpath($meta);
			if(count($children) > 0)
			{
				$meta_data = $children[0];
			}
		}
	}
	return htmlspecialchars($meta_data);
}

function file_meta_update()
{
	global $file_meta_xml, $file_meta_path;
	$file = '';
	// meta data to update
	$meta_update = array();
	foreach($_REQUEST as $key => $value)
	{
		// temp array for storing meta key and its value
		$ar = array();
		// if request key is file
		if($key == "file") $file = $value;
		if(strpos($key, "meta_") === 0) {
			$ar = explode('_',$key);
			$meta_update[$ar[1]] = $value;
		}
	}
	// find the file in the XML document
	$file_item = $file_meta_xml->xpath('//files/file[@name="'.$file.'"]');
	// if the file is not found
	if(count($file_item) === 0) {
		$file_item = $file_meta_xml->addChild('file');
		$file_item->addAttribute('name', $file);
	}
	
	// loop through all meta keys to update
	foreach($meta_update as $key => $value)
	{
		$children = $file_item[0]->xpath($key);
		if(count($children) > 0)
		{
			// remove existing tag
			unset($children[0][0]);
		}
		$file_item[0]->addChild($key, htmlspecialchars($value));
		
	}
	
	XMLsave($file_meta_xml, $file_meta_path);
	
	if($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') { 
		ob_clean();
		die("Meta Data saved");
	}
}


