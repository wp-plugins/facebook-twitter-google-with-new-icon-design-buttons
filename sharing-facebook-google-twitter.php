<?php
/*
Plugin Name: Social share-Facebook Like and Share , Twitter , Google  buttons
Plugin URI:
Description: Puts Facebook Like and Share,Twitter,Google +1,Google buzz share buttons of your choice above or below your posts.
Author: serverdeath3
Version: 1.0
Author URI:
*/
    // New share
/*
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/


// ACTION AND FILTERS

add_action('init', 'bos_2_inite');

add_filter('the_content', 'bos_2_cont');

add_filter('the_excerpt', 'bos_2_excer');

add_filter('plugin_action_links', 'bos_3_link', 10, 2 );

add_action('admin_menu', 'bos_3_menu');

add_shortcode( 'bos_3', 'bos_3_short' );

// PUBLIC FUNCTIONS

function bos_2_inite() {
	// DISABLED IN THE ADMIN PAGES
	if (is_admin()) {
		return;
	}

	//GET ARRAY OF STORED VALUES
	$option = bos_3_get_options_stored();

	if ($option['active_buttons']['facebook']==true) {
		wp_enqueue_script('bos_3_facebook', 'http://static.ak.fbcdn.net/connect.php/js/FB.Share');
	}
	if ($option['active_buttons']['buzz']==true) {
		wp_enqueue_script('bos_3_buzz', 'http://www.google.com/buzz/api/button.js');
	}
	if ($option['active_buttons']['google1']==true) {
		wp_enqueue_script('bos_3_google1', 'http://apis.google.com/js/plusone.js');
	}
	if ($option['active_buttons']['twitter']==true) {
		wp_enqueue_script('bos_3_twitter', 'http://platform.twitter.com/widgets.js');
	}
}    


function bos_3_menu() {
	add_options_page('Share Facebook twitter google', 'Share Facebook twitter google ', 'manage_options', 'bos_3_options', 'bos_3_options');
}


function bos_3_link($links, $file) {
	static $this_plugin;
	if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);
 
	if ($file == $this_plugin){
		$settings_link = '<a href="admin.php?page=bos_3_options">'.__("Settings").'</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
} 


function bos_2_cont ($content) {
	return bos_3 ($content, 'the_content');
}


function bos_2_excer ($content) {
	return bos_3 ($content, 'the_excerpt');
}


function bos_3 ($content, $filter, $link='', $title='') {
	static $last_execution = '';

	// IF the_excerpt IS EXECUTED AFTER the_content MUST DISCARD ANY CHANGE MADE BY the_content
	if ($filter=='the_excerpt' and $last_execution=='the_content') {
		// WE TEMPORARILY REMOVE CONTENT FILTERING, THEN CALL THE_EXCERPT
		remove_filter('the_content', 'bos_2_cont');
		$last_execution = 'the_excerpt';
		return the_excerpt();
	}
	if ($filter=='the_excerpt' and $last_execution=='the_excerpt') {
		// WE RESTORE THE PREVOIUSLY REMOVED CONTENT FILTERING, FOR FURTHER EXECUTIONS (POSSIBLY NOT INVOLVING 
		add_filter('the_content', 'bos_2_cont');
	}

	// IF THE "DISABLE" CUSTOM FIELD IS FOUND, BLOCK EXECUTION
	// unless the shortcode was used in which case assume the disable
	// should be overridden, allowing us to disable general settings for a page
	// but insert buttons in a particular content area
	$custom_field_disable = get_post_custom_values('bos_3_disable');
	if ($custom_field_disable[0]=='yes' and $filter!='shortcode') {
		return $content;
	}
	
	//GET ARRAY OF STORED VALUES
	$option = bos_3_get_options_stored();

	if ($filter!='shortcode') {
		if (is_single()) {
			if (!$option['show_in']['posts']) { return $content; }
		} else if (is_singular()) {
			if (!$option['show_in']['pages']) {
				return $content;
			}
		} else if (is_home()) {
			if (!$option['show_in']['home_page']) {	return $content; }
		} else if (is_tag()) {
			if (!$option['show_in']['tags']) { return $content; }
		} else if (is_category()) {
			if (!$option['show_in']['categories']) { return $content; }
		} else if (is_date()) {
			if (!$option['show_in']['dates']) { return $content; }
		} else if (is_author()) {
			//IF DISABLED INSIDE PAGES
			if (!$option['show_in']['authors']) { return $content; }
		} else if (is_search()) {
			if (!$option['show_in']['search']) { return $content; }
		} else {
			// IF NONE OF PREVIOUS, IS DISABLED
			return $content;
		}
	}
	$first_shown = false; // NO PADDING FOR THE FIRST BUTTON
	
	// IF LINK AND TITLE ARE NOT SET, USE DEFAULT GET_PERMALINK AND GET_THE_TITLE FUNCTIONS
	if ($link=='' and $title=='') {
		$link = get_permalink();
		$title = get_the_title();
	}

	$out = '<div style="height:33px; padding-top:2px; padding-bottom:2px; clear:both;" class="bos_3">';
	if ($option['active_buttons']['facebook']==true) {
		$first_shown = true;
		
		// REMOVE HTTP:// FROM STRING
		$facebook_link = (substr($link,0,7)=='http://') ? substr($link,7) : $link;
		$out .= '<div style="float:left; width:100px;" class="bos_3_facebook"> 
				<a name="fb_share" type="button_count" href="http://www.facebook.com/sharer.php" share_url="'.$facebook_link.'">Share</a>
			</div>';
	}
	if ($option['active_buttons']['facebook_like']==true) {
		$padding = 'padding-left:10px;';
		if (!$first_shown) {
			$first_shown = true;
			$padding = '';
		}
		// OPTION facebook_like_text FILTERING
		$option_facebook_like_text = ($option['facebook_like_text']=='recommend') ? 'recommend' : 'like';
		$out .= '<div style="float:left; width:'.$option['facebook_like_width'].'px; '.$padding.'" class="bos_3_facebook_like"> 
				<iframe src="http://www.facebook.com/plugins/like.php?href='.urlencode($link).'&amp;layout=button_count&amp;show_faces=false&amp;width='.$option['facebook_like_width'].'&amp;action='.$option_facebook_like_text.'&amp;colorscheme=light&amp;height=27"
					scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:'.$option['facebook_like_width'].'px; height:27px;" allowTransparency="true"></iframe>
			</div>';
		// FACEBOOK LIKE SEND BUTTON CURRENTLY IN FBML MODE - WILL BE MERGED IN THE LIKE BUTTON WHEN FACEBOOK RELEASES IT	
		if ($option['facebook_like_send']) {
			static $facebook_like_send_script_inserted = false;
			if (!$facebook_like_send_script_inserted) {
				$out .= '<script src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script>';
				$facebook_like_send_script_inserted = true;
			}
			$out .= '<div style="float:left; width:50px; padding-left:10px;" class="bos_3_facebook_like_send">
				<fb:send href="'.$link.'" font=""></fb:send>
				</div>';
		}	
	}

	if ($option['active_buttons']['buzz']==true) {
		$padding = 'padding-left:10px;';
		if (!$first_shown) {
			$first_shown = true;
			$padding = '';
		}
		$out .= '<div style="float:left; '.$padding.'" class="bos_3_buzz"> 
				<a title="Post to Google Buzz" class="google-buzz-button" href="http://www.google.com/buzz/post" data-button-style="small-count"
					data-url="'.$link.'"></a>
			</div>';
	}



	if ($option['active_buttons']['google1']==true) {
		$padding = 'padding-left:10px;';
		if (!$first_shown) {
			$first_shown = true;
			$padding = '';
		}
		$data_count = ($option['google1_count']) ? '' : 'count="false"';
		$out .= '<div style="float:left; width:'.$option['google1_width'].'px; '.$padding.'" class="bos_3_google1"> 
				<g:plusone size="medium" href="'.$link.'" '.$data_count.'></g:plusone>
			</div>';
	}
	if ($option['active_buttons']['twitter']==true) {
		$padding = 'padding-left:10px;';
		if (!$first_shown) {
			$first_shown = true;
			$padding = '';
		}
		$data_count = ($option['twitter_count']) ? 'horizontal' : 'none';
		$out .= '<div style="float:left; width:'.$option['twitter_width'].'px; '.$padding.'" class="bos_3_twitter"> 
				<a href="http://twitter.com/share" class="twitter-share-button" data-count="'.$data_count.'"
					data-text="'.$title.stripslashes($option['twitter_text']).'" data-url="'.$link.'">Tweet</a> 
			</div>';
	}



	// REMEMBER LAST FILTER EXECUTION TO HANDLE the_excerpt VS the_content
	$last_execution = $filter;
	
	if ($filter=='shortcode') {
		return $out;
	}

	if ($option['position']=='both') {
		return $out.$content.$out;
	} else if ($option['position']=='below') {
		return $content.$out;
	} else {
		return $out.$content;
	}
}

function bos_3_options () {

	$option_name = 'bos_3';

   
	if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	$active_buttons = array(
		'facebook_like'=>'Facebook like',
		'facebook'=>' Facebook share',
		'twitter'=>'Twitter',
		'google1'=>'Google "+1"',
		'buzz'=>'Google Buzz',

	);	

	$show_in = array(
		'posts'=>'Single posts',
		'pages'=>'Pages',
		'home_page'=>'Home page',
		'tags'=>'Tags',
		'categories'=>'Categories',
		'dates'=>'Date based archives',
		'authors'=>'Author archives',
		'search'=>'Search results',
	);
	
	$out = '';
	
	// See if the user has posted us some information
	if( isset($_POST['bos_3_position'])) {
		$option = array();

		foreach (array_keys($active_buttons) as $item) {
			$option['active_buttons'][$item] = (isset($_POST['bos_3_active_'.$item]) and $_POST['bos_3_active_'.$item]=='on') ? true : false;
		}
		foreach (array_keys($show_in) as $item) {
			$option['show_in'][$item] = (isset($_POST['bos_3_show_'.$item]) and $_POST['bos_3_show_'.$item]=='on') ? true : false;
		}
		$option['position'] = esc_html($_POST['bos_3_position']);
		$option['facebook_like_width'] = esc_html($_POST['bos_3_facebook_like_width']);
		$option['facebook_like_text'] = ($_POST['bos_3_facebook_like_text']=='recommend') ? 'recommend' : 'like';
		$option['facebook_like_send'] = (isset($_POST['bos_3_facebook_like_send']) and $_POST['bos_3_facebook_like_send']=='on') ? true : false;
		$option['google1_count'] = (isset($_POST['bos_3_google1_count']) and $_POST['bos_3_google1_count']=='on') ? true : false;
		$option['google1_width'] = esc_html($_POST['bos_3_google1_width']);
		$option['twitter_count'] = (isset($_POST['bos_3_twitter_count']) and $_POST['bos_3_twitter_count']=='on') ? true : false;
		$option['twitter_width'] = esc_html($_POST['bos_3_twitter_width']);
		$option['twitter_text'] = esc_html($_POST['bos_3_twitter_text']);
		
		update_option($option_name, $option);
		// Put a settings updated message on the screen
		$out .= '<div class="updated"><p><strong>'.__('Settings saved.', 'menu-test' ).'</strong></p></div>';
	}
	
	//GET ARRAY OF STORED VALUES
	$option = bos_3_get_options_stored();
	
	$sel_above = ($option['position']=='above') ? 'selected="selected"' : '';
	$sel_below = ($option['position']=='below') ? 'selected="selected"' : '';
	$sel_both  = ($option['position']=='both' ) ? 'selected="selected"' : '';

	$sel_like      = ($option['facebook_like_text']=='like'     ) ? 'selected="selected"' : '';
	$sel_recommend = ($option['facebook_like_text']=='recommend') ? 'selected="selected"' : '';
	
	$facebook_like_show_send_button = ($option['facebook_like_send']) ? 'checked="checked"' : '';
	$google1_count = ($option['google1_count']) ? 'checked="checked"' : '';
	$twitter_count = ($option['twitter_count']) ? 'checked="checked"' : '';

	// SETTINGS FORM

	$out .= '
	<style>
	#bos_3_form h3 { cursor: default; }
	#bos_3_form td { vertical-align:top; padding-bottom:15px; }
	</style>
	
	<div class="wrap">
	<h2>'.__( 'Facebook Like and Share,Twitter,Google +1,Google buzz buttons', 'menu-test' ).'</h2>
	<div id="poststuff" style="padding-top:10px; position:relative;">

	<div style="float:left; width:74%; padding-right:1%;">

		<form id="bos_3_form" name="form1" method="post" action="">

		<div class="postbox">
		<h3>'.__("General options", 'menu-test' ).'</h3>
		<div class="inside">
			<table>
			<tr><td style="width:130px;">'.__("Active share buttons", 'menu-test' ).':</td>
			<td>';
		
			foreach ($active_buttons as $name => $text) {
				$checked = ($option['active_buttons'][$name]) ? 'checked="checked"' : '';
				$out .= '<div style="width:250px; float:left;">
						<input type="checkbox" name="bos_3_active_'.$name.'" '.$checked.' /> '
						. __($text, 'menu-test' ).' &nbsp;&nbsp;</div>';

			}

			$out .= '</td></tr>
			<tr><td>'.__("Show buttons in these pages", 'menu-test' ).':</td>
			<td>';

			foreach ($show_in as $name => $text) {
				$checked = ($option['show_in'][$name]) ? 'checked="checked"' : '';
				$out .= '<div style="width:250px; float:left;">
						<input type="checkbox" name="bos_3_show_'.$name.'" '.$checked.' /> '
						. __($text, 'menu-test' ).' &nbsp;&nbsp;</div>';

			}

			$out .= '</td></tr>
			<tr><td>'.__("Position", 'menu-test' ).':</td>
			<td><select name="bos_3_position">
				<option value="above" '.$sel_above.' > '.__('before the post', 'menu-test' ).'</option>
				<option value="below" '.$sel_below.' > '.__('after the post', 'menu-test' ).'</option>
				<option value="both"  '.$sel_both.'  > '.__('before  and after the post', 'menu-test' ).'</option>
				</select>
			</td></tr>
			</table>
		</div>
		</div>

		<div class="postbox">
		<h3>'.__("Facebook Like  options", 'menu-test' ).'</h3>
		<div class="inside">
			<table>
			<tr><td>'.__("Button width", 'menu-test' ).':</td>
			<td>
				<input type="text" name="bos_3_facebook_like_width" value="'.stripslashes($option['facebook_like_width']).'" size="10"> px<br />
				<span class="description">'.__("default: 100", 'menu-test' ).'</span>
			</td></tr>
			<tr><td>'.__("Button text", 'menu-test' ).':</td>
			<td>
				<select name="bos_3_facebook_like_text">
					<option value="like" '.$sel_like.' > '.__('like', 'menu-test' ).'</option>
					<option value="recommend" '.$sel_recommend.' > '.__('recommend', 'menu-test' ).'</option>
				</select>
			</td></tr>
			<tr><td>'.__("Show Send button", 'menu-test' ).':</td>
			<td>
				<input type="checkbox" name="bos_3_facebook_like_send" '.$facebook_like_show_send_button.' />
			</td></tr>
			</table>
		</div>
		</div>

		<div class="postbox">
		<h3>'.__("Google +1  options", 'menu-test' ).'</h3>
		<div class="inside">
			<table>
			<tr><td>'.__("Button width", 'menu-test' ).':</td>
			<td>
				<input type="text" name="bos_3_google1_width" value="'.stripslashes($option['google1_width']).'" size="10"> px<br />
				<span class="description">'.__("default: 90", 'menu-test' ).'</span>
			</td></tr>
			<tr><td>'.__("Show counter", 'menu-test' ).':</td>
			<td>
				<input type="checkbox" name="bos_3_google1_count" '.$google1_count.' />
			</td></tr>
			</table>
		</div>
		</div>
	
		<div class="postbox">
		<h3>'.__("Twitter  options", 'menu-test' ).'</h3>
		<div class="inside">
			<table>
			<tr><td style="width:130px;">'.__("Button width", 'menu-test' ).':</td>
			<td>
				<input type="text" name="bos_3_twitter_width" value="'.stripslashes($option['twitter_width']).'" size="10"> px<br />
				<span class="description">'.__("default: 110", 'menu-test' ).'</span>
			</td></tr>
			<tr><td>'.__("Additional text", 'menu-test' ).':</td>
			<td>
				<input type="text" name="bos_3_twitter_text" value="'.stripslashes($option['twitter_text']).'" size="25"><br />
				<span class="description">'.__("optional text added at the end of every tweet, e.g. ' (via @authorofblogentry)'.
			   ", 'menu-test' ).'</span>
			</td></tr>
			<tr><td>'.__("Show counter", 'menu-test' ).':</td>
			<td>
				<input type="checkbox" name="bos_3_twitter_count" '.$twitter_count.' />
			</td></tr>
			</table>
		</div>
		</div>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="'.esc_attr('Save Changes').'" />
		</p>

		</form>

	</div>
	

	</div>

	</div>

	';
	echo $out;
}


// SHORTCODE FOR ALL ACTIVE BUTTONS
function bos_3_short ($atts) {
	return bos_3 ('', 'shortcode');
}


//FUNCTION AVAILABLE FOR EXTERNAL INCLUDING INSIDE THEMES AND OTHER PLUGINS
function bos_3_publish ($link='', $title='') {
	return bos_3 ('', 'shortcode', $link, $title);
}



// PRIVATE FUNCTIONS

function bos_3_get_options_stored () {
	//GET ARRAY OF STORED VALUES
	$option = get_option('bos_3');
	 
	if ($option===false) {
		//OPTION NOT IN DATABASE, SO WE INSERT DEFAULT VALUES
		$option = bos_3_get_options_default();
		add_option('bos_3', $option);
	} else if ($option=='above' or $option=='below') {
		// Versions below 1.2.0 compatibility
		$option = bos_3_get_options_default($option);
	} else if(!is_array($option)) {
		// Versions below 1.2.2 compatibility
		$option = json_decode($option, true);
	}
	
	// Versions below 1.4.1 compatibility
	if (!isset($option['facebook_like_text'])) {
		$option['facebook_like_text'] = 'like';
	}

	// Versions below 1.4.5 compatibility
	if (!isset($option['facebook_like_width'])) {
		$option['facebook_like_width'] = '100';
	}
	if (!isset($option['twitter_width'])) {
		$option['twitter_width'] = '110';
	}

	// Versions below 1.5.1 compatibility
	if (!isset($option['twitter_count'])) {
		$option['twitter_count'] = true;
	}

	// Versions below 1.6.1 compatibility
	if (!isset($option['google1_count'])) {
		$option['google1_count'] = true;
	}
	if (!isset($option['google1_width'])) {
		$option['google1_width'] = '90';
	}
	return $option;
}

function bos_3_get_options_default ($position='above') {
	$option = array();
	$option['active_buttons'] = array('facebook'=>false, 'twitter'=>true,  'buzz'=>false,  'facebook_like'=>true, 'hyves'=>false,  'google1'=>false);
	$option['position'] = $position;
	$option['show_in'] = array('posts'=>true, 'pages'=>true, 'home_page'=>true, 'tags'=>true, 'categories'=>true, 'dates'=>true, 'authors'=>true, 'search'=>true);
	$option['facebook_like_text'] = 'like';
	$option['facebook_like_send'] = false;
	$option['facebook_like_width'] = '100';
	$option['google1_count'] = true;
	$option['google1_width'] = '90';
	$option['twitter_count'] = true;
	$option['twitter_text'] = '';
	$option['twitter_width'] = '110';
	return $option;
}
