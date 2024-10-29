<?php
/*
Plugin Name: Automated Editing
Plugin URI: http://wasistlos.waldemarstoffel.com/plugins-fur-wordpress/automated-editing
Description: If working with a lot of editors, who either don't know how to use WP or don't pay attention, this plugin helps you by adding an excerpt automatically to every post, that doesn't have one.
Version: 2.0.1
Author: Stefan Crämer
Author URI: http://www.stefan-craemer.com
License: GPL3
Text Domain: automated-editing
Domain Path: /languages
*/

/*  Copyright 2011 - 2016 Stefan Crämer (email : support@atelier-fuenf.de)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

/* Stop direct call */

defined('ABSPATH') OR exit;

if (!defined('AED_PATH')) define( 'AED_PATH', plugin_dir_path(__FILE__) );
if (!defined('AED_BASE')) define( 'AED_BASE', plugin_basename(__FILE__) );

# loading the framework
if (!class_exists('A5_Excerpt')) require_once AED_PATH.'class-lib/A5_ExcerptClass.php';
if (!class_exists('A5_FormField')) require_once AED_PATH.'class-lib/A5_FormFieldClass.php';
if (!class_exists('A5_OptionPage')) require_once AED_PATH.'class-lib/A5_OptionPageClass.php';
if (!class_exists('A5_DynamicFiles')) require_once AED_PATH.'class-lib/A5_DynamicFileClass.php';

#loading plugin specific classes
if (!class_exists('AED_Admin')) require_once AED_PATH.'class-lib/AED_AdminClass.php';

class AutomatedEditing {
	
	static $options;
	
	function __construct() {
		
		// import laguage files
		load_plugin_textdomain('automated-editing', false , basename(dirname(__FILE__)).'/languages');
	
		add_filter('plugin_row_meta', array($this, 'register_links'), 10, 2);
		add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2);
		
		add_action('wp_insert_post', array($this, 'save_excerpt'), 300);
		add_action('edit_attachment', array($this, 'save_excerpt'), 300);
		add_action('add_attachment', array($this, 'save_excerpt'), 300);
		
		register_activation_hook(__FILE__, array($this, '_install'));
		register_deactivation_hook(__FILE__, array($this, '_uninstall'));
		
		if (true == WP_DEBUG):
		
			add_action('wp_before_admin_bar_render', array($this, 'admin_bar_menu'));
		
		endif;
		
		if (is_multisite()) :
		
			$plugins = get_network_option(NULL, 'active_sitewide_plugins');
			
			if (isset($plugins[AE_BASE])) :
		
				self::$options = get_network_option(NULL, 'aed_options');
				
			else :
			
				$plugins = get_option('active_plugins');
				
				if (in_array(AED_BASE, $plugins)) self::$options = get_option('aed_options');
				
			endif;
			
		else:
		
			$plugins = get_option('active_plugins');
			
			if (in_array(AED_BASE, $plugins)) self::$options = get_option('aed_options');
		
		endif;
		
		$AED_Admin = new AED_Admin(self::$options['multisite']);
		
	}

	/**
	 *
	 * Additional links in the plugin row
	 *
	 */
	function register_links($links, $file) {
		
		if ($file == AED_BASE) :
			
			$links[] = '<a href="http://wordpress.org/extend/plugins/automated-editing/faq/" target="_blank">'.__('FAQ', 'automated-editing').'</a>';
			$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7RG539AT3TDMA" target="_blank">'.__('Donate', 'automated-editing').'</a>';
		
		endif;
		
		return $links;
	
	}
	
	function plugin_action_links( $links, $file ) {
		
		if ($file == AED_BASE) array_unshift($links, '<a href="'.admin_url( 'options-general.php?page=automated-editing-settings' ).'">'.__('Settings', 'automated-editing').'</a>');
	
		return $links;
	
	}
	
	// Activate plugin
	
	function _install() {
		
		$screen = get_current_screen();
		
		$default = array(
			'multisite' => false,
			'readmore' => false,
			'thumbnail' => false,
			'exclude_from_more_tag' => array (
				'nav_menu_item' => 'nav_menu_item'
			)
		);
		
		if (is_multisite() && $screen->is_network) :
		
			$default['multisite'] = true;
		
			add_network_option(NULL, 'aed_options', $default);
			
		else:
		
			add_option('aed_options', $default);
			
		endif;
		
	}
	
	// Cleaning on deactivation
	
	function _uninstall() {
		
		$screen = get_current_screen();
		
		if (is_multisite() && $screen->is_network) :
		
			delete_network_option(NULL, 'aed_options');
			
		else:
		
			delete_option('aed_options');
			
		endif;
		
	}
	
	/**
	 *
	 * Checking if there is any excerpt; if not, build it; same with thumbnail and more tag (if wanted)
	 *
	 */	
	function save_excerpt($post_id) {
		
		if (!isset($_POST['post_type']) || empty($_POST['content'])) return;
		
		$content = $_POST['content'];
		
		if (!empty($_POST)) :
		
			remove_action('wp_insert_post', array($this, 'save_excerpt'), 300);
			remove_action('edit_attachment', array($this, 'save_excerpt'), 300);
			remove_action('add_attachment', array($this, 'save_excerpt'), 300);
			
			$excerpt = (isset($_POST['excerpt'])) ? $_POST['excerpt'] : '';
			
			$post_type = $_POST['post_type'];
			
			// auto excerpt
			
			if (!isset(self::$options['exclude_from_excerpt']) || !in_array($post_type, self::$options['exclude_from_excerpt'])) :
			
				if (post_type_supports($post_type, 'excerpt') && empty($excerpt)) :
				
					$args = array(
						'content' => $content,
						'offset' => self::$options['offset'],
						'type' => self::$options['excerpt_style'],
						'count' => self::$options['excerpt_length'],
						'filter' => false,
						'shortcode' => false,
						'links' => false
					);
			
					$excerpt = A5_Excerpt::text($args);
					
				endif;
				
			endif;
			
			// auto meta description
			
			if (isset(self::$options['metadesc']) && defined( 'WPSEO_FILE' )) :
			
				$seo_desc = (isset($_POST['yoast_wpseo_metadesc'])) ? trim($_POST['yoast_wpseo_metadesc']) : '';
				
				if (!isset(self::$options['exclude_from_seo_desc']) || !in_array($post_type, self::$options['exclude_from_seo_desc'])) :
				
					if (in_array($post_type, array('post', 'page', 'attachment')) && empty($seo_desc)) :
					
						$args = array(
							'content' => $content,
							'offset' => self::$options['offset'],
							'type' => self::$options['excerpt_style'],
							'count' => self::$options['excerpt_length'],
							'filter' => false,
							'shortcode' => false,
							'links' => false
						);
				
						$seo_desc = A5_Excerpt::text($args);
						
						if (strlen($seo_desc) > 156) $seo_desc = trim(substr($seo_desc, 0, 154)).' &#8230;';
						
					endif;
					
				endif;
				
			endif;
			
			// auto more tag
			
			if (!isset(self::$options['exclude_from_more_tag']) || !in_array($post_type, self::$options['exclude_from_more_tag'])) :
			
				if (post_type_supports($post_type, 'editor') && self::$options['readmore']) :
						
					if (!strstr($content, '<!--more-->')) :
					
						$length = (self::$options['readmore_length']) ? self::$options['readmore_length']*2 : 10;
					
						$short=array_slice(preg_split('/([\t.!?]\s+)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE), 0, $length);
								
						$first_part = trim(implode($short));
						
						$second_part = substr($content, strlen($first_part));
						
						$content = (strlen($second_part) != 0) ? $first_part.'<!--more-->'.$second_part : $content;
						
					endif;
				
				endif;
			
			endif;
			
			// auto thumbnail
			
			if (!isset(self::$options['exclude_from_thumbnail']) || !in_array($post_type, self::$options['exclude_from_thumbnail'])) :
			
				if (self::$options['thumbnail'] && post_type_supports($post_type, 'thumbnail') && !has_post_thumbnail()) :
				
					$args = array(
						'post_type' => 'attachment',
						'posts_per_page' => 1,
						'post_status' => null,
						'post_parent' => $post_id,
						'order' => 'ASC'
					);
					
					$attachments = get_posts( $args );
					
					if ($attachments) $attachment_id = $attachments[0]->ID;
					
					else $attachment_id = $this->check_for_images($post_id);
					
					if ($attachment_id) :
						
						set_post_thumbnail($post_id, $attachment_id);
						
					else :
					
						set_post_thumbnail($post_id, self::$options['thumbnail_id']);
							
					endif;
					
				endif;
				
			endif;
			
			// save the post
			
			$aed_post = array(
				'ID' => $post_id,
				'post_excerpt' => strip_tags($excerpt), 
				'post_content' => $content
			);
			
			wp_update_post( $aed_post );
			
			if (isset($seo_desc)) update_post_meta($post_id, '_yoast_wpseo_metadesc', $seo_desc);
			
			add_action('wp_insert_post', array($this, 'save_excerpt'), 300);
			add_action('edit_attachment', array($this, 'save_excerpt'), 300);
			add_action('add_attachment', array($this, 'save_excerpt'), 300);
			
		endif;
		
	}
	
	/**
	 *
	 * Checking if there are images in the content and return the attachment id, if existing
	 *
	 */	
	 function check_for_images($id) {
		
		$content = get_post_field('post_content', $id);
		
		$check = @do_shortcode($content);
		
		if (is_wp_error($check)) :
		
			echo '<pre>';var_dump($check);echo '</pre>';die();
			
		endif;
		
		$images = preg_match_all('#(?:<a[^>]+?href=["|\'](?P<link_url>[^\s]+?)["|\'][^>]*?>\s*)?(?P<img_tag><img[^>]+?src=["|\'](?P<img_url>[^\s]+?)["|\'].*?>){1}(?:\s*</a>)?#is', $check, $matches);
		
		if (0 == $images) :
		
			if (strstr($content, 'gallery')) :
			
				$ids = preg_match_all('#ids=["|\']([^\s]+?)["|\']#is', $content, $matches);
				
				$ids = explode(',', $matches[1][0]);
				
				$attachment_id = trim($ids[0]);
				
				if (isset($attachment_id)) return $attachment_id;
			
			endif;
			
			return false;
			
		endif;
		
		$images = $matches['img_url'];
		
		$upload_dir = wp_upload_dir();
		
		foreach ($images as $image) :
		
			if (strstr($image, $upload_dir['baseurl'])) :
			
				global $wpdb;
		
				$upload_dir = wp_upload_dir();
				
				$image = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $image );
				
				$image = str_replace( $upload_dir['baseurl'] . '/', '', $image );
				
				$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $image ) );
				
				if (isset($attachment_id)) return $attachment_id;
			
			endif;
		
		endforeach;
		
		return false;
		 
	 }
	 
	 /**
	 *
	 * Adds a link to the settings to the admin bar in case WP_DEBUG is true
	 *
	 */
	function admin_bar_menu() {
		
		global $wp_admin_bar;
		
		if (!is_super_admin() || !is_admin_bar_showing()) return;
		
		$wp_admin_bar->add_node(array('parent' => '', 'id' => 'a5-framework', 'title' => 'A5 Framework'));
		
		$wp_admin_bar->add_node(array('parent' => 'a5-framework', 'id' => 'a5-automated-editig', 'title' => 'Automated Editing', 'href' => admin_url('options-general.php?page=automated-editing-settings')));
		
	}

} // end of class

$AutomatedEditing = new AutomatedEditing;

?>