<?php

/**
 *
 * Class Automated Editing Admin
 *
 * @ Automated Editing
 *
 * building and sanitizing admin pages
 *
 */

class AED_Admin extends A5_OptionPage{
	
	private static $options;
	
	function __construct($multisite) {
	
		add_action('admin_init', array($this, 'initialize_settings'));
		add_action('contextual_help', array($this, 'add_help_text'), 10, 3);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));	
		
		if ($multisite) :
		
			add_action('network_admin_menu', array($this, 'add_site_admin_menu'));
				
			self::$options = get_network_option(NULL, 'aed_options');
			
		else :
			
			add_action('admin_menu', array($this, 'add_admin_menu'));
		
			self::$options = get_option('aed_options');
			
		endif;
		
		$dynamic_css = new A5_DynamicFiles('admin', 'css', 'all', array('settings_page_automated-editing-settings', 'toplevel_page_automated-editing-settings'), true);
		
		$eol = "\n";
		$tab = "\t";
		
		A5_DynamicFiles::$admin_styles .= $eol.'/* CSS portion of Automated editing */'.$eol.$eol;
		
		A5_DynamicFiles::$admin_styles .= '.automated-editing-container {'.$eol.$tab.'float: left;'.$eol.$tab.'padding: 5px;'.$eol.$tab.'margin: 5px;'.$eol.$tab.'border: solid 1px;'.$eol.$tab.'width: 31%;'.$eol.$tab.'min-width: 200px;'.$eol.$tab.'min-height: 220px;'.$eol.'}'.$eol;
		
		A5_DynamicFiles::$admin_styles .= '.automated-editing-table {'.$eol.$tab.'border-spacing: 0px;'.$eol.$tab.'padding: 5px;'.$eol.$tab.'margin-top: 10px;'.$eol.'}'.$eol;
		
	}
	
	/**
	 *
	 * Make all the admin stuff draggable
	 *
	 */
	function enqueue_scripts($hook){
		
		if ('settings_page_automated-editing-settings' != $hook && 'toplevel_page_automated-editing-settings' != $hook) return;
		
		$min = (SCRIPT_DEBUG == false) ? '.min.' : '.';
		
		wp_enqueue_script('dashboard');
		
		if (wp_is_mobile()) wp_enqueue_script('jquery-touch-punch');
		
		// getting the media uploader
		
		if ( function_exists( 'wp_enqueue_media' ) ) :
			
			wp_enqueue_media();
			
			wp_register_script( 'a5-media-upload-script', plugins_url('automated-editing/media-uploader'.$min.'js'), array( 'jquery' ), '1.0', true );
			
			wp_enqueue_script('a5-media-upload-script');
			
		endif;
		
	}

	/**
	 *
	 * Initialize the admin screen of the plugin
	 *
	 */
	function initialize_settings() {
		
		register_setting('aed_options', 'aed_options', array($this, 'validate'));
		
		add_settings_section('aed_options', '', array($this, 'aed_main_admin_section'), 'aed_main_admin');
		
		add_settings_field('aed_excerpt_length', __('How long should the excerpt be?', 'automated-editing'), array($this, 'aed_excerpt_length_field'), 'aed_main_admin', 'aed_options');
		
		add_settings_field('aed_excerpt_style', __('Do you want to count words, sentences or letters?', 'automated-editing'), array($this, 'aed_excerpt_style_field'), 'aed_main_admin', 'aed_options');
		
		add_settings_field('aed_offset', __('Offset (how many sentences should be ignored in the beginning):', 'automated-editing'), array($this, 'aed_offset_field'), 'aed_main_admin', 'aed_options');
		
		add_settings_field('aed_read_more', __('Should the plugin insert the &#39;more&#39; tag as well?', 'automated-editing'), array($this, 'aed_readmore_field'), 'aed_main_admin', 'aed_options');
		
		if (!empty(self::$options['readmore'])) add_settings_field('aed_read_more_length', __('After how many sentences do you want to insert the &#39;more&#39; tag?', 'automated-editing'), array($this, 'aed_readmore_length_field'), 'aed_main_admin', 'aed_options');
		
		add_settings_field('aed_thumbnail', __('Should the plugin attach the post thumbnail as well?', 'automated-editing'), array($this, 'aed_thumbnail_field'), 'aed_main_admin', 'aed_options');
		
		if (!empty(self::$options['thumbnail'])) add_settings_field('aed_default_image', __('Use a default image as thumbnail (if there&#39;s no thumbnail defined).', 'automated-editing'), array($this, 'aed_default_thumbnail_input'), 'aed_main_admin', 'aed_options');
		
		if (defined( 'WPSEO_FILE' )) add_settings_field('aed_seo_desc', __('Should the plugin automatically create the meta description for the Yoast WPSEO plugin as well?', 'automated-editing'), array($this, 'aed_seo_desc_field'), 'aed_main_admin', 'aed_options', array(__('For the meta description, Automated Editing will take the same settings as for the excerpt. However, the description will be limited to 154 letters (plus &#39;&#8230;&#39;).', 'automated-editing')));
		
		add_settings_section('aed_options', __('Excerpt', 'automated-editing'), array($this, 'aed_support_excerpt_section'), 'aed_excerpt_support');
		
		if (true == WP_DEBUG && true == WP_DEBUG_LOG) :
		
			$filename = WP_CONTENT_DIR.'/debug.log';
			
			$errorlog = file($filename);
			
			$logsize = count($errorlog);
		
			$entry = ($logsize > 1) ? __('entries', 'automated-editing') : __('entry', 'automated-editing');
			
			if ($logsize > 0) :
			
				add_settings_section('aed_options', sprintf(__('Empty debug log (%d %s):', 'automated-editing'), count($errorlog), $entry), array($this, 'display_reset_section'), 'aed_debug_settings');
				
				add_settings_field('reset_debug_log', __('You can empty the debug log here, if necessary.', 'automated-editing'), array($this, 'reset_debug_field'), 'aed_debug_settings', 'aed_options');
				
			endif;
			
		endif;
		
		add_settings_field('aed_pt_exclusion_excerpt', __('Check, to exclude', 'automated-editing'), array($this, 'aed_pt_excl_excerpt_field'), 'aed_excerpt_support', 'aed_options');
		
		add_settings_section('aed_options', __('Yoast WPSEO Meta Description', 'automated-editing'), array($this, 'aed_support_seo_desc_section'), 'aed_seo_desc_support');
		
		add_settings_field('aed_pt_exclusion_seo_desc', __('Check, to exclude', 'automated-editing'), array($this, 'aed_pt_excl_seo_desc_field'), 'aed_seo_desc_support', 'aed_options');
		
		add_settings_section('aed_options', __('More Tag', 'automated-editing'), array($this, 'aed_support_more_tag_section'), 'aed_more_tag_support');
		
		add_settings_field('aed_pt_exclusion_more_tag', __('Check, to exclude', 'automated-editing'), array($this, 'aed_pt_excl_more_tag_field'), 'aed_more_tag_support', 'aed_options');
		
		add_settings_section('aed_options', __('Thumbnail', 'automated-editing'), array($this, 'aed_support_thumbnail_section'), 'aed_thumbnail_support');
		
		add_settings_field('aed_pt_exclusion_thumbnail', __('Check, to exclude', 'automated-editing'), array($this, 'aed_pt_excl_thumbnail_field'), 'aed_thumbnail_support', 'aed_options');
	
	}
	
	function aed_main_admin_section() {
		
		echo '<p>'.__('Choose length of it and whether it counts sentences, words or letters.', 'automated-editing').'</p>';
	
	}
	
	function aed_excerpt_length_field() {
		
		a5_number_field('excerpt_length', 'aed_options[excerpt_length]', @self::$options['excerpt_length'], false, array('step' => 1, 'min' => 0));
		
	}
	
	function aed_offset_field() {
		
		a5_number_field('offset', 'aed_options[offset]', @self::$options['offset'], false, array('step' => 1, 'min' => 0));
		
	}
	
	function aed_excerpt_style_field() {
				
		$select = array (array('sentences', __('Sentences', 'automated-editing')) , array('words', __('Words', 'automated-editing')), array('letters', __('Letters', 'automated-editing')));
		
		a5_select('excerpt_style', 'aed_options[excerpt_style]', $select, @self::$options['excerpt_style']);

	}
	
	function aed_readmore_field() {
		
		a5_checkbox('readmore', 'aed_options[readmore]', @self::$options['readmore']);
		
	}
	
	function aed_readmore_length_field() {
		
		a5_number_field('readmore_length', 'aed_options[readmore_length]', @self::$options['readmore_length'], false, array('step' => 1, 'min' => 0));
		
	}
	
	function aed_thumbnail_field() {
		
		a5_checkbox('thumbnail', 'aed_options[thumbnail]', @self::$options['thumbnail']);
		
	}
	
	function aed_default_thumbnail_input() {
		
		$label = __('Enter a URL', 'automated-editing');
		
		if (function_exists('wp_enqueue_media')) :
		
			self::tag_it(a5_button('upload-thumbnail', 'thumbnail', __('Select Image'), false, array('class' => 'button upload-button'), false), 'p', 1, array('id' => 'thumbnail_upload', 'style' => 'display: none;'), true);
				
			self::tag_it('<img src="'.@self::$options['thumbnail_url'].'" alt="'.__('Preview').'" style="max-width: 320px; height: auto;" />', 'p', 1, array('id' => 'thumbnail_preview', 'style' => 'display: none;'), true);
			
			self::tag_it(a5_button('remove-thumbnail', 'thumbnail', __('Remove Image'), false, array('class' => 'button remove-button'), false), 'p', 1, array('id' => 'thumbnail_remove', 'style' => 'display: none;'), true);
			
			$label = __('Or enter a URL', 'automated-editing');
			
		endif;
		
		self::tag_it($label, 'p', false, false, true);
				
		a5_url_field('thumbnail_url', 'aed_options[thumbnail_url]', @self::$options['thumbnail_url'], false, array('style' => 'min-width: 350px; max-width: 500px;'));
		
	}
	
	function aed_seo_desc_field($labels) {
		
		a5_checkbox('metadesc', 'aed_options[metadesc]', @self::$options['metadesc'], $labels[0]);
		
	}
	
	function aed_support_excerpt_section() {
		
		self::tag_it(__('Post Types with Excerpt:', 'automated-editing'), 'p', false, false, true);
	
	}
	
	function aed_pt_excl_excerpt_field() {
		
		$post_types = get_post_types('', 'objects');
		
		foreach ($post_types as $post_type) :
		
			if (post_type_supports( $post_type->name, 'excerpt' )) $post_boxes[] = array('exclude_from_excerpt-'.$post_type->name, 'aed_options[exclude_from_excerpt]['.$post_type->name.']', $post_type->name, $post_type->label, false, @self::$options['exclude_from_excerpt'][$post_type->name]);
		
		endforeach;
		
		a5_checkgroup(false, false, $post_boxes);
		
	}
	
	function aed_support_seo_desc_section() {
		
		self::tag_it(__('Post Types with meta description:', 'automated-editing'), 'p', false, false, true);
	
	}
	
	function aed_pt_excl_seo_desc_field() {
		
		$post_types = get_post_types('', 'objects');
		
		foreach ($post_types as $post_type) :
		
			if (in_array( $post_type->name, array('post', 'page', 'attachment') )) $post_boxes[] = array('exclude_from_seo_desc-'.$post_type->name, 'aed_options[exclude_from_seo_desc]['.$post_type->name.']', $post_type->name, $post_type->label, false, @self::$options['exclude_from_seo_desc'][$post_type->name]);
		
		endforeach;
		
		a5_checkgroup(false, false, $post_boxes);
		
	}
	
	function aed_support_more_tag_section() {
		
		self::tag_it(__('Post Types with content (for more tag):', 'automated-editing'), 'p', false, false, true);
	
	}
	
	function aed_pt_excl_more_tag_field() {
		
		$post_types = get_post_types('', 'objects');
		
		foreach ($post_types as $post_type) :
		
			if (post_type_supports( $post_type->name, 'editor' ) && 'nav_menu_item' != $post_type->name) $post_boxes[] = array('exclude_from_more_tag-'.$post_type->name, 'aed_options[exclude_from_more_tag]['.$post_type->name.']', $post_type->name, $post_type->label, false, @self::$options['exclude_from_more_tag'][$post_type->name]);
		
		endforeach;
		
		if (!is_array(self::$options['exclude_from_more_tag']) || !array_key_exists('nav_menu_item', self::$options['exclude_from_more_tag'])) :
			
			self::$options['exclude_from_more_tag']['nav_menu_item'] = 'nav_menu_item';
			
			if (is_plugin_active_for_network( plugin_basename( __FILE__ ))) update_network_option(NULL, 'aed_options', self::$options);
			
			else update_option('aed_options', self::$options);
			
		endif;
		
		a5_checkgroup(false, false, $post_boxes);
		
	}
	
	function aed_support_thumbnail_section() {
		
		self::tag_it(__('Post Types with thumbnail:', 'automated-editing'), 'p', false, false, true);
	
	}
	
	function aed_pt_excl_thumbnail_field() {
		
		$post_types = get_post_types('', 'objects');
		
		foreach ($post_types as $post_type) :
		
			if (post_type_supports( $post_type->name, 'thumbnail' )) $post_boxes[] = array('exclude_from_thumbnail-'.$post_type->name, 'aed_options[exclude_from_thumbnail]['.$post_type->name.']', $post_type->name, $post_type->label, false, @self::$options['exclude_from_thumbnail'][$post_type->name]);
		
		endforeach;
		
		a5_checkgroup(false, false, $post_boxes);
		
	}
	
	function display_reset_section() {
		
		self::tag_it(__('Empty the debug log.', 'automated-editing'), 'p');
	
	}
	
	function reset_debug_field() {
		
		submit_button(__('OK', 'automated-editing'), 'secondary', 'aed_options[reset_debug_log]', true, array('id' => 'reset_debug_log'));
		
	}
		
	/**
	 *
	 * Add options-page for single site
	 *
	 */
	function add_admin_menu() {
		
		add_options_page('Automated Editing '.__('Settings', 'automated-editing'), '<img alt="" src="'.plugins_url('automated-editing/img/a5-icon-11.png').'"> Automated Editing', 'administrator', 'automated-editing-settings', array($this, 'build_options_page'));
		
	}
	
	/**
	 *
	 * Add menu page for multisite
	 *
	 */
	function add_site_admin_menu() {
		
		add_menu_page('Automated Editing '.__('Settings', 'automated-editing'), 'Automated Editing', 'administrator', 'automated-editing-settings', array($this, 'build_options_page'), plugins_url('automated-editing/img/a5-icon-16.png'));
		
	}
	
	/**
	 *
	 * Actually build the option pages
	 *
	 */
	function build_options_page() {
		
		// tabed browsing
		
		$active = (isset($_GET['tab'])) ? $_GET['tab'] : 'main_options';
		
		// this is only necessary if the plugin is activated for network
		
		if (@$_GET['action'] == 'update') :
		
			$input = $_POST['aed_options'];
			
			self::$options = $this->validate($input);
			
			update_network_option(NULL, 'aed_options', self::$options);
			
			$this->initialize_settings();
		
		endif;
		
		// the main options page begins here
		
		$eol = "\n";
		
		$tab = "\t";
		
		$dtab = $tab.$tab;
		
		// navigation
		
		self::open_page('Automated Editing', __('http://wasistlos.waldemarstoffel.com/plugins-fur-wordpress/automated-editing', 'automated-editing'), 'automated-editing');
		
		if (is_plugin_active_for_network(AED_BASE)) settings_errors();
		
		$tabs ['main_options'] = array( 'class' => ($active == 'main_options') ? ' nav-tab-active' : '', 'text' => __('Main Options', 'automated-editing'));
		$tabs ['excerpts'] = array( 'class' => ($active == 'excerpts') ? ' nav-tab-active' : '', 'text' => __('Check Excerpts', 'automated-editing'));
		if (isset(self::$options['metadesc'])) $tabs ['yoast_metadesc'] = array( 'class' => ($active == 'yoast_metadesc') ? ' nav-tab-active' : '', 'text' => __('Check Yoast WPSEO meta descriptions', 'automated-editing'));
		if (true === self::$options['readmore']) $tabs ['moretag'] = array( 'class' => ($active == 'moretag') ? ' nav-tab-active' : '', 'text' => __('Check More Tag', 'automated-editing'));
		if (true === self::$options['thumbnail']) $tabs ['thumbnails'] = array( 'class' => ($active == 'thumbnails') ? ' nav-tab-active' : '', 'text' => __('Check Thumbnails', 'automated-editing'));
		
		$args = array(
			'page' => 'automated-editing-settings',
			'menu_items' => $tabs
		);
		
		self::nav_menu($args);

		$action = (is_plugin_active_for_network(AED_BASE)) ? '?page=automated-editing-settings&tab='.$active.'&action=update' : 'options.php';
		
		self::open_form($action);
		
		// nonce and stuff which is the same for all tabs
		
		wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
		wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
		
		a5_hidden_field('tab', 'aed_options[tab]', $active, true);
		
		settings_fields('aed_options');

		// the actual options tab
			
		if ($active == 'main_options') :
		
			$sections[] = 'aed_excerpt_support';
			
			if (isset(self::$options['metadesc'])) $sections[] = 'aed_seo_desc_support';
			
			if (true === self::$options['readmore']) $sections[] = 'aed_more_tag_support';
			
			if (true === self::$options['thumbnail']) $sections[] = 'aed_thumbnail_support';
		
			$atts = array('class' => 'automated-editing-container');
		
			self::open_tab();
			
			self::sortable('top', self::postbox(__('How should the excerpt be built?', 'automated-editing'), 'main-options', 'aed_main_admin'));
			
			self::wrapper('middle', __('Excluding Post Types', 'automated-editing'), 'post-types', $sections, $atts);
			
			submit_button();
			
			if (WP_DEBUG === true) :
			
				self::sortable('deep-down', self::debug_info(self::$options, __('Debug Info', 'automated-editing')));
				
				if (true == WP_DEBUG_LOG) self::sortable('errorlog-info', self::debug_log_info(__('Error Log', 'automated-editing'), 'aed_debug_settings'));
				
			endif;
			
			self::close_tab();
			
		endif;
		
		// tab for the excerpts
		
		if ($active == 'excerpts') :
		
			self::open_tab();
			
			echo self::open_sortable('top');
			
			echo self::open_postbox(__('Check for old posts without excerpt', 'automated-editing'), 'check-excerpt');
			
			if (!isset(self::$options['noexcerpt']) || empty(self::$options['noexcerpt'])) :
			
				_e('Please check first for posts without excerpt.', 'automated-editing');
				 
			else :
			
				foreach (self::$options['noexcerpt'] as $post_type => $count) :
				
					self::tag_it(sprintf(__('You have %d %s without excerpt.', 'automated-editing'), $count, $this->get_nicename($post_type)), 'p', false, false, true);
					
				endforeach;
			
			endif;
			
			self::tag_it(self::tag_it(__('Scanning for posts without excerpt can take a long time. Please be patient.', 'automated-editing'), 'b'), 'p', false, false, true);
			
			submit_button(__('Check Now', 'automated-editing'), 'secondary', 'aed_options[check_posts]', true, array('id' => 'check_posts'));
			
			echo self::close_postbox();
			
			echo self::close_sortable();
			
			// this will appear after a scan has found post( type)s without excerpts
			
			if (isset(self::$options['noexcerpt']) && !empty(self::$options['noexcerpt'])) :
			
				echo self::open_sortable('middle');
			
				echo self::open_postbox(__('Fix old posts without excerpt', 'automated-editing'), 'fix-excerpt');
			
				self::tag_it(self::tag_it(__('Again, you should be patient with this action.', 'automated-editing'), 'b').' '.__('If you have a lot of posts that have no excerpt, it might take a while to fix them.', 'automated-editing'), 'p', 1, false, true);
				
				self::tag_it(__('If you have no other settings for the excerpt, the plugin will take the first three sentences of the post as default.', 'automated-editing'), 'p', false, false, true);
				
				submit_button(__('Fix Posts', 'automated-editing'), 'primary', 'aed_options[fix_posts]', true, array('id' => 'fix_posts'));
				
				echo self::close_postbox();
			
				echo self::close_sortable();
				
			endif;
			
			// list of unfixed post( type)s
			
			if (isset(self::$options['empty_excerpts'])) :
			
				echo self::open_sortable('bottom');
			
				$this->do_the_table (self::$options['empty_excerpts'], __('excerpt', 'automated-editing'), 'unfixed-excerpt');
				
				echo self::close_sortable();
			
			endif;
			
			if (WP_DEBUG === true) :
			
				self::sortable('deep-down', self::debug_info(self::$options, __('Debug Info', 'automated-editing')));
				
				if (true == WP_DEBUG_LOG) self::sortable('errorlog-info', self::debug_log_info(__('Error Log', 'automated-editing'), 'aed_debug_settings'));
				
			endif;
			
			self::close_tab();
			
		endif;
		
		// tab for the yoast metadesriptions
		
		if ($active == 'yoast_metadesc') :
		
			self::open_tab();
			
			echo self::open_sortable('top');
			
			echo self::open_postbox(__('Check for old posts without Yoast WPSEO meta descriptions', 'automated-editing'), 'check-seo-desc');
			
			if (!isset(self::$options['nometadesc']) || empty(self::$options['nometadesc'])) :
			
				_e('Please check first for posts without meta description.', 'automated-editing');
				 
			else :
			
				foreach (self::$options['nometadesc'] as $post_type => $count) :
				
					self::tag_it(sprintf(__('You have %d %s without meta description.', 'automated-editing'), $count, $this->get_nicename($post_type)), 'p', false, false, true);
					
				endforeach;
			
			endif;
			
			self::tag_it(self::tag_it(__('Scanning for posts without meta description can take a long time. Please be patient.', 'automated-editing'), 'b'), 'p', false, false, true);
			
			submit_button(__('Check Now', 'automated-editing'), 'secondary', 'aed_options[check_posts]', true, array('id' => 'check_posts'));
			
			echo self::close_postbox();
			
			echo self::close_sortable();
			
			// this will appear after a scan has found post( type)s without meta descriptions
			
			if (isset(self::$options['nometadesc']) && !empty(self::$options['nometadesc'])) :
			
				echo self::open_sortable('middle');
			
				echo self::open_postbox(__('Fix old posts without meta description', 'automated-editing'), 'fix-metadesc');
			
				self::tag_it(self::tag_it(__('Again, you should be patient with this action.', 'automated-editing'), 'b').' '.__('If you have a lot of posts that have no meta description, it might take a while to fix them.', 'automated-editing'), 'p', 1, false, true);
				
				self::tag_it(__('If you have no other settings for the metadesription, the plugin will take the first three sentences of the post as default and limit the meta description automatically to 156 signs.', 'automated-editing'), 'p', false, false, true);
				
				submit_button(__('Fix Posts', 'automated-editing'), 'primary', 'aed_options[fix_posts]', true, array('id' => 'fix_posts'));
				
				echo self::close_postbox();
				
				echo self::close_sortable();
				
			endif;
			
			// list of unfixed post( type)s
			
			if (isset(self::$options['empty_metadescs'])) :
			
				echo self::open_sortable('bottom');
			
				$this->do_the_table (self::$options['empty_metadescs'], __('meta description', 'automated-editing'), 'unfixed-metadesc');
				
				echo self::close_sortable();
			
			endif;
			
			if (WP_DEBUG === true) :
			
				self::sortable('deep-down', self::debug_info(self::$options, __('Debug Info', 'automated-editing')));
				
				if (true == WP_DEBUG_LOG) self::sortable('errorlog-info', self::debug_log_info(__('Error Log', 'automated-editing'), 'aed_debug_settings'));
				
			endif;
			
			self::close_tab();
			
		endif;
		
		// tab for more tags
		
		if ($active == 'moretag') :
		
			self::open_tab();
			
			echo self::open_sortable('top');
			
			echo self::open_postbox(__('Check for old posts without &#39;more&#39; tag', 'automated-editing'), 'check-tag');
			
			if (!isset(self::$options['nomoretag']) || empty(self::$options['nomoretag'])) :
			
				_e('Please check first for posts without &#39;more&#39; tag.', 'automated-editing');
				 
			else :
			
				foreach (self::$options['nomoretag'] as $post_type => $count) :
				
					self::tag_it(sprintf(__('You have %d %s without &#39;more&#39; tag.', 'automated-editing'), $count, $this->get_nicename($post_type)), 'p', false, false, true);
					
				endforeach;
			
			endif;
			
			self::tag_it(self::tag_it(__('Scanning for posts without &#39;more&#39; tag can take a long time. Please be patient.', 'automated-editing'), 'b'), 'p', false, false, true);
			
			submit_button(__('Check Now', 'automated-editing'), 'secondary', 'aed_options[check_posts]', true, array('id' => 'check_posts'));
			
			echo self::close_postbox();
			
			echo self::close_sortable();
			
			if (isset(self::$options['nomoretag']) && !empty(self::$options['nomoretag'])) :
			
				echo self::open_sortable('middle');
			
				echo self::open_postbox(__('Fix old posts without &#39;more&#39; tag', 'automated-editing'), 'fix-tag');
				
				self::tag_it(self::tag_it(__('Again, you should be patient with this action.', 'automated-editing'), 'b').' '.__('If you have a lot of posts that have no &#39;more&#39; tag, it might take a while to fix them.', 'automated-editing'), 'p', 1, false, true);
				
				submit_button(__('Fix &#39;More&#39; Tags', 'automated-editing'), 'primary', 'aed_options[fix_tags]', true, array('id' => 'fix_moretag'));
				
				echo self::close_postbox();
				
				echo self::close_sortable();
				
			endif;
			
			// this will appear after a scan has found posts without more tags
			
			if (isset(self::$options['empty_tags'])) :
			
				echo self::open_sortable('bottom');
			
				$this->do_the_table (self::$options['empty_tags'], __('&#39;more&#39; tag', 'automated-editing'), 'unfixed-tag');
				
				echo self::close_sortable();
			
			endif;
			
			if (WP_DEBUG === true) :
			
				self::sortable('deep-down', self::debug_info(self::$options, __('Debug Info', 'automated-editing')));
				
				if (true == WP_DEBUG_LOG) self::sortable('errorlog-info', self::debug_log_info(__('Error Log', 'automated-editing'), 'aed_debug_settings'));
				
			endif;;
			
			self::close_tab();
			
		endif;
		
		// tab for thumbnails
		
		if ($active == 'thumbnails') :
		
			self::open_tab();
		
			echo self::open_sortable('top');
			
			echo self::open_postbox(__('Check for old posts without thumbnail', 'automated-editing'), 'check-thumbnail');
			
			if (!isset(self::$options['nothumb']) || empty(self::$options['nothumb'])) :
			
				_e('Please check first for posts without thumbnail.', 'automated-editing');
				 
			else :
			
				foreach (self::$options['nothumb'] as $post_type => $count) :
				
					echo '<p>'.sprintf(__('You have %d %s without thumbnail.', 'automated-editing'), $count, $this->get_nicename($post_type)).'</p>';
					
				endforeach;
			
			endif;
			
			self::tag_it(self::tag_it(__('Scanning for posts without featured image can take a long time. Please be patient.', 'automated-editing'), 'b'), 'p', false, false, true);
			
			submit_button(__('Check Now', 'automated-editing'), 'secondary', 'aed_options[check_posts]', true, array('id' => 'check_posts'));
			
			echo self::close_postbox();
			
			echo self::close_sortable();
			
			if (isset(self::$options['thumbnail_id']) && self::$options['old_thumb_id'] != self::$options['thumbnail_id']) :
			
				echo self::open_sortable('upper-middle');
				
				echo self::open_postbox(__('New Default Thumbnail', 'automated-editing'), 'check-thumbnail');
				
				self::tag_it(__('The old default thumbnail is on the left side. You can exchange it with the new one in all posts where it is used.', 'automated-editing'), 'p', false, false, true);
				
				$img_src = wp_get_attachment_url(self::$options['old_thumb_id']);
				
				self::tag_it('<img src="'.$img_src.'" alt="'.__('Preview').'" style="max-width: 320px; height: auto;" />', 'p', 1, array('id' => 'old_thumbnail', 'style' => 'float: left; margin-right: 10px;'), true);
				
				self::tag_it('<img src="'.self::$options['thumbnail_url'].'" alt="'.__('Preview').'" style="max-width: 320px; height: auto;" />', 'p', 1, array('id' => 'new_thumbnail', 'style' => 'float: left;'), true);
				
				self::clear_it();
				
				self::tag_it(self::tag_it(__('Changing the default featured image in all posts can take a long time. Please be patient.', 'automated-editing'), 'b'), 'p', false, false, true);
				
				submit_button(__('Change Default Thumbnail', 'automated-editing'), 'secondary', 'aed_options[change_thumbnail]', true, array('id' => 'change_thumbnail'));
				
				echo self::close_postbox();
				
				echo self::close_sortable();
			
			endif;
			
			if (isset(self::$options['nothumb']) && !empty(self::$options['nothumb'])) :
			
				echo self::open_sortable('lower-middle');
			
				echo self::open_postbox(__('Fix old posts without thumbnail', 'automated-editing'), 'fix-thumbnail');
				
				self::tag_it(self::tag_it(__('Again, you should be patient with this action.', 'automated-editing'), 'b').' '.__('If you have a lot of posts without thumbnail, it might take a while to fix them.', 'automated-editing'), 'p', 1, false, true);
				
				self::tag_it(__('If there are images attached to the post, the plugin will take the first of those images as the post thumbnail.', 'automated-editing'), 'p', false, false, true);
				
				submit_button(__('Fix Thumbnails', 'automated-editing'), 'primary', 'aed_options[fix_thumbs]', true, array('id' => 'fix_thumbs'));
				
				echo self::close_sortable();
			
				echo self::close_postbox();
				
			endif;
			
			// this will appear after a scan has found posts without thumbnail
			
			if (isset(self::$options['empty_thumbs'])) :
			
				echo self::open_sortable('bottom');
			
				$this->do_the_table (self::$options['empty_thumbs'], __('thumbnail', 'automated-editing'), 'unfixed-thumbnail');
				
				echo self::close_sortable();
			
			endif;
			
			if (WP_DEBUG === true) :
			
				self::sortable('deep-down', self::debug_info(self::$options, __('Debug Info', 'automated-editing')));
				
				if (true == WP_DEBUG_LOG) self::sortable('errorlog-info', self::debug_log_info(__('Error Log', 'automated-editing'), 'aed_debug_settings'));
				
			endif;
			
			self::close_tab();
			
		endif;
		
		self::close_page();

	}
	
	/**
	 *
	 * The 'machine'; this function handles all requests from the options page
	 *
	 * Sanitizing, validating and checking / fixing old posts happens here
	 *
	 */
	function validate($input) {
		
		if (isset($input['reset_debug_log'])) :
		
			$filename = WP_CONTENT_DIR.'/debug.log';
			
			file_put_contents($filename, '');
			
			add_settings_error('aed_options', 'empty-debug', __('Debug Log emptied.', 'automated-editing'), 'updated');
			
			return self::$options;
		
		endif;
		
		$active = @$input['tab'];
		
		switch ($active) :
		
			case 'main_options':
			
				// saving excerpt settings
			
				self::$options['excerpt_length']=trim($input['excerpt_length']);
		
				if (!is_numeric(self::$options['excerpt_length']) && self::$options['excerpt_length'] != '') :
				
					self::$options['excerpt_length']='';
					
					add_settings_error('aed_options', 'not-numeric-excerpt-length', __('Please enter a numeric value for the length of the excerpt.', 'automated-editing'), 'error');
					
				endif;
				
				self::$options['offset']=trim($input['offset']);
				
				if (!is_numeric(self::$options['offset']) && !empty(self::$options['offset'])) :
				
					self::$options['offset']='';
					
					add_settings_error('aed_options', 'not-numeric-offset', __('Please enter a numeric value for the offset.', 'automated-editing'), 'error');
					
				endif;
				
				self::$options['excerpt_style'] = $input['excerpt_style'];
				
				// saving metadesc settings
				
				self::$options['metadesc'] = (isset($input['metadesc'])) ? true : NULL;
				
				if (defined( 'WPSEO_FILE' ) && isset($input['metadesc'])) unset($input['metadesc']);
				
				// saving settings for the 'more' tag
				
				self::$options['readmore'] = (isset($input['readmore'])) ? true : false;
				
				if (isset($input['readmore_length'])) :
				
					self::$options['readmore_length']=trim($input['readmore_length']);
				
					if (!is_numeric(self::$options['readmore_length']) && self::$options['readmore_length'] != '') :
					
						self::$options['readmore_length']='';
						
						add_settings_error('aed_options', 'not-numeric-readmore', __('Please enter a numeric value for the number of sentences before the &#39;read more&#39; tag.', 'automated-editing'), 'error');
						
					endif;
				
				endif;
				
				if (false == self::$options['readmore']) unset(self::$options['readmore_length']);
				
				// saving thumbnail settings
				
				self::$options['thumbnail'] = (isset($input['thumbnail'])) ? true : false;
				
				if (!empty($input['thumbnail_url'])) :
					
					self::$options['thumbnail_url'] = $input['thumbnail_url'];
						
					global $wpdb;
					
					$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", self::$options['thumbnail_url'] ));
					
					$thumbnail_id = $attachment[0];
					
					if (!isset($thumbnail_id)) :
					
						add_settings_error('aed_options', 'not-uploaded', __('The image doesn&#39;t seem to be in the database. Please upload an image that you want to use as default thumbnail.', 'automated-editing'), 'error');
						
						self::$options['thumbnail_id'] = NULL;
						
						unset(self::$options['thumbnail_url']);
						
					else :
					
						if (!isset(self::$options['old_thumb_id'])) :
						
							self::$options['old_thumb_id'] = $thumbnail_id;
							
						else :
						
							self::$options['old_thumb_id'] = self::$options['thumbnail_id'];
						
						endif;
					
						self::$options['thumbnail_id'] = $thumbnail_id;
					
					endif;
					
				endif;
					
				if (false == self::$options['thumbnail'] || empty($input['thumbnail_url'])) unset(self::$options['thumbnail_url'], self::$options['thumbnail_id'], self::$options['old_thumb_id']);	
				
				self::$options['exclude_from_excerpt'] = @$input['exclude_from_excerpt'];
				self::$options['exclude_from_seo_desc'] = @$input['exclude_from_seo_desc'];
				self::$options['exclude_from_more_tag'] = @$input['exclude_from_more_tag'];
				self::$options['exclude_from_thumbnail'] = @$input['exclude_from_thumbnail'];
				
				if (is_array(self::$options['exclude_from_excerpt']) && isset(self::$options['empty_excerpts'])) :
				
					foreach (self::$options['empty_excerpts'] as $post_type => $list) :
				
						if (in_array($post_type, self::$options['exclude_from_excerpt'])) unset(self::$options['noexcerpt'][$post_type], self::$options['empty_excerpts'][$post_type]);
					
					endforeach;
					
				endif;
				
				if (is_array(self::$options['exclude_from_seo_desc']) && isset(self::$options['empty_metadescs'])) :
				
					foreach (self::$options['empty_metadescs'] as $post_type => $list) :
				
						if (in_array($post_type, self::$options['exclude_from_seo_desc'])) unset(self::$options['nometadesc'][$post_type], self::$options['empty_metadescs'][$post_type]);
					
					endforeach;
					
				endif;
				
				if (is_array(self::$options['exclude_from_more_tag']) && isset(self::$options['empty_tags'])) :
				
					foreach (self::$options['empty_tags'] as $post_type => $list) :
					
						if (in_array($post_type, self::$options['exclude_from_more_tag'])) unset(self::$options['nomoretag'][$post_type], self::$options['empty_tags'][$post_type]);
					
					endforeach;
					
				endif;
				
				if (is_array(self::$options['exclude_from_thumbnail']) && isset(self::$options['empty_thumbs'])) :
				
					foreach (self::$options['empty_thumbs'] as $post_type => $list) :
					
						if (in_array($post_type, self::$options['exclude_from_thumbnail'])) unset(self::$options['nothumb'][$post_type], self::$options['empty_thumbs'][$post_type]);
						
					endforeach;
					
				endif;
				
				self::$options['exclude_from_more_tag']['nav_menu_item'] = 'nav_menu_item';
				
				if (is_plugin_active_for_network(AED_BASE)) add_settings_error('aed_options', 'aed-saved', __('Settings saved.'), 'updated');
				
				break;
				
			case 'excerpts':
				
				$blogs = $this->get_all_blog_ids();
				
				// check for excluded post types first
				
				$post_types = get_post_types();
					
				$excludes = (is_array(self::$options['exclude_from_excerpt'])) ? self::$options['exclude_from_excerpt'] : array();
				
				$types_to_check = array_diff($post_types, $excludes);
				
				// checking for empty excerpts
			
				if (isset($input['check_posts'])) :
					
					self::$options['empty_excerpts'] = array();
					
					foreach ($types_to_check as $post_type) :
					
						if (post_type_supports($post_type, 'excerpt')) :
						
							self::$options['noexcerpt'][$post_type] = 0;
					
							foreach ($blogs as $id) :
						
								$results = $this->check_posts('excerpt', $id, $post_type);
							
								self::$options['noexcerpt'][$post_type] += $results[0];
								
								if ($results[0] > 0) self::$options['empty_excerpts'][$post_type][$results[1]] = $results[2];
								
							endforeach;
							
							$type = (self::$options['noexcerpt'][$post_type] > 0) ? 'error' : 'updated';
							
							$nicename = $this->get_nicename($post_type);
							
							$message = sprintf(__('There have been %d %s without excerpt detected.', 'automated-editing'), self::$options['noexcerpt'][$post_type], $nicename);
							
							add_settings_error('aed_options', 'aed-scanned-'.$post_type, $message, $type);
							
							if (self::$options['noexcerpt'][$post_type] == 0) unset(self::$options['noexcerpt'][$post_type]);
							
						endif;
						
					endforeach;
					
					if (!isset(self::$options['noexcerpt']) || empty(self::$options['noexcerpt'])) :
					
						$errors = get_settings_errors('aed_options');
					
						if (!$errors) :
						
							add_settings_error('aed_options', 'aed-scan-useless', __('There was nothing to scan.', 'automated-editing'), 'updated');
							
						endif;
					
					else :
					
						if (count(self::$options['noexcerpt']) == 0) unset(self::$options['noexcerpt']);
						
						if (count(self::$options['empty_excerpts']) == 0) unset(self::$options['empty_excerpts']);
					
					endif;
					
				endif;
				
				// try to repair empty excerpts
				
				if (isset($input['fix_posts'])) :
				
					$list_to_fix = self::$options['empty_excerpts'];
					
					foreach ($list_to_fix as $post_type => $blogs) :
					
						$count = 0;
					
						foreach ($blogs as $blog => $items) :
						
							$results = $this->fix_posts('excerpt', $post_type, $items);
							
							$count += $results[0];
							
							self::$options['noexcerpt'][$post_type] -= $results[0];
							
							self::$options['empty_excerpts'][$post_type][$blog] = $results[1];
							
							if (count(self::$options['empty_excerpts'][$post_type][$blog]) == 1) unset(self::$options['empty_excerpts'][$post_type][$blog]);
							
						endforeach;
						
						if (count(self::$options['empty_excerpts'][$post_type]) == 0) unset(self::$options['empty_excerpts'][$post_type]);
						
						if (self::$options['noexcerpt'][$post_type] == 0) unset(self::$options['noexcerpt'][$post_type]);
						
						$type = ($count > 0) ? 'updated' : 'error';
							
						$nicename = $this->get_nicename($post_type);
						
						$message = sprintf(__('%d %s have been fixed successfully.', 'automated-editing'), $count, $nicename);
						
						add_settings_error('aed_options', 'aed-fixed-'.$post_type, $message, $type);
						
					endforeach;
					
					if (count(self::$options['empty_excerpts']) == 0) unset(self::$options['empty_excerpts']);
					
					if (count(self::$options['noexcerpt']) == 0) unset(self::$options['noexcerpt']);
					
				endif;
				
				break;
				
			case 'yoast_metadesc':
				
				$blogs = $this->get_all_blog_ids();
				
				// check for excluded post types first
				
				$post_types = array('post', 'page', 'attachment');
					
				$excludes = (is_array(self::$options['exclude_from_seo_desc'])) ? self::$options['exclude_from_seo_desc'] : array();
				
				$types_to_check = array_diff($post_types, $excludes);
				
				// checking for empty meta descriptions
			
				if (isset($input['check_posts'])) :
					
					self::$options['empty_metadescs'] = array();
					
					foreach ($types_to_check as $post_type) :
					
						self::$options['nometadesc'][$post_type] = 0;
					
						foreach ($blogs as $id) :
					
							$results = $this->check_posts('metadesc', $id, $post_type);
						
							self::$options['nometadesc'][$post_type] += $results[0];
							
							if ($results[0] > 0) self::$options['empty_metadescs'][$post_type][$results[1]] = $results[2];
							
						endforeach;
						
						$type = (self::$options['nometadesc'][$post_type] > 0) ? 'error' : 'updated';
						
						$nicename = $this->get_nicename($post_type);
						
						$message = sprintf(__('There have been %d %s without meta descriptions detected.', 'automated-editing'), self::$options['nometadesc'][$post_type], $nicename);
						
						add_settings_error('aed_options', 'aed-scanned-'.$post_type, $message, $type);
						
						if (self::$options['nometadesc'][$post_type] == 0) unset(self::$options['nometadesc'][$post_type]);
						
					endforeach;
					
					if (!isset(self::$options['nometadesc']) || empty(self::$options['nometadesc'])) :
					
						$errors = get_settings_errors('aed_options');
					
						if (!$errors) :
						
							add_settings_error('aed_options', 'aed-scan-useless', __('There was nothing to scan.', 'automated-editing'), 'updated');
							
						endif;
					
					else :
					
						if (count(self::$options['nometadesc']) == 0) unset(self::$options['nometadesc']);
					
						if (count(self::$options['empty_metadescs']) == 0) unset(self::$options['empty_metadescs']);
					
					endif;
					
				endif;
				
				// try to repair empty meta descriptions
				
				if (isset($input['fix_posts'])) :
				
					$list_to_fix = self::$options['empty_metadescs'];
					
					foreach ($list_to_fix as $post_type => $blogs) :
					
						$count = 0;
					
						foreach ($blogs as $blog => $items) :
						
							$results = $this->fix_posts('metadesc', $post_type, $items);
							
							$count += $results[0];
							
							self::$options['nometadesc'][$post_type] -= $results[0];
							
							self::$options['empty_metadescs'][$post_type][$blog] = $results[1];
							
							if (count(self::$options['empty_metadescs'][$post_type][$blog]) == 1) unset(self::$options['empty_metadescs'][$post_type][$blog]);
							
						endforeach;
						
						if (count(self::$options['empty_metadescs'][$post_type]) == 0) unset(self::$options['empty_metadescs'][$post_type]);
						
						if (self::$options['nometadesc'][$post_type] == 0) unset(self::$options['nometadesc'][$post_type]);
						
						$type = ($count > 0) ? 'updated' : 'error';
							
						$nicename = $this->get_nicename($post_type);
						
						$message = sprintf(__('%d %s have been fixed successfully.', 'automated-editing'), $count, $nicename);
						
						add_settings_error('aed_options', 'aed-fixed-'.$post_type, $message, $type);
						
					endforeach;
					
					if (count(self::$options['empty_metadescs']) == 0) unset(self::$options['empty_metadescs']);
					
					if (count(self::$options['nometadesc']) == 0) unset(self::$options['nometadesc']);
					
				endif;
				
				break;	
				
			case 'moretag':
			
				$blogs = $this->get_all_blog_ids();
				
				// check for excluded post types first
				
				$post_types = get_post_types();
					
				$excludes = (is_array(self::$options['exclude_from_more_tag'])) ? self::$options['exclude_from_more_tag'] : array();
				
				$types_to_check = array_diff($post_types, $excludes);
				
				// checking for missing more tags
			
				if (isset($input['check_posts'])) :
					
					self::$options['empty_tags'] = array();
					
					foreach ($types_to_check as $post_type) :
					
						if (post_type_supports($post_type, 'editor')) :
						
							self::$options['nomoretag'][$post_type] = 0;
					
							foreach ($blogs as $id) :
						
								$results = $this->check_posts('tag', $id, $post_type);
								
								self::$options['nomoretag'][$post_type] += $results[0];
								
								if ($results[0] > 0) self::$options['empty_tags'][$post_type][$results[1]] = $results[2];
							
							endforeach;
							
							$type = (self::$options['nomoretag'][$post_type] > 0) ? 'error' : 'updated';
							
							$nicename = $this->get_nicename($post_type);
							
							$message = sprintf(__('There have been %d %s without &#39;more&#39; tag detected.', 'automated-editing'), self::$options['nomoretag'][$post_type], $nicename);
			
							add_settings_error('aed_options', 'aed-scanned-'.$post_type, $message, $type);
							
							if (self::$options['nomoretag'][$post_type] == 0) unset(self::$options['nomoretag'][$post_type]);
							
						endif;
						
					endforeach;
					
					if (!isset(self::$options['nomoretag']) || empty(self::$options['nomoretag'])) :
					
						$errors = get_settings_errors('aed_options');
					
						if (!$errors) :
						
							add_settings_error('aed_options', 'aed-scan-useless', __('There was nothing to scan.', 'automated-editing'), 'updated');
							
						endif;
					
					else :
					
						if (count(self::$options['nomoretag']) == 0) unset(self::$options['nomoretag']);
					
						if (count(self::$options['empty_tags']) == 0) unset(self::$options['empty_tags']);
					
					endif;
					
				endif;
				
				// try to repair missing more tags
				
				if (isset($input['fix_tags'])) :
				
					$list_to_fix = self::$options['empty_tags'];
					
					foreach ($list_to_fix as $post_type => $blogs) :
					
						$count = 0;
					
						foreach ($blogs as $blog => $items) :
						
							$results = $this->fix_posts('tag', $post_type, $items);
							
							$count += $results[0];
							
							self::$options['nomoretag'][$post_type] -= $results[0];
							
							self::$options['empty_tags'][$post_type][$blog] = $results[1];
							
							if (count(self::$options['empty_tags'][$post_type][$blog]) == 1) unset(self::$options['empty_tags'][$post_type][$blog]);
							
						endforeach;
						
						if (count(self::$options['empty_tags'][$post_type]) == 0) unset(self::$options['empty_tags'][$post_type]);
						
						if (self::$options['nomoretag'][$post_type] == 0) unset(self::$options['nomoretag'][$post_type]);
						
						$type = ($count > 0) ? 'updated' : 'error';
							
						$nicename = $this->get_nicename($post_type);
						
						$message = sprintf(__('%d %s have been fixed successfully.', 'automated-editing'), $count, $nicename);
						
						add_settings_error('aed_options', 'aed-fixed-'.$post_type, $message, $type);
						
					endforeach;
					
					if (count(self::$options['empty_tags']) == 0) unset(self::$options['empty_tags']);
					
					if (count(self::$options['nomoretag']) == 0) unset(self::$options['nomoretag']);
					
				endif;
				
				break;	
				
			case 'thumbnails':
			
				$blogs = $this->get_all_blog_ids();
				
				// check for excluded post types first
				
				$post_types = get_post_types();
					
				$excludes = (is_array(self::$options['exclude_from_thumbnail'])) ? self::$options['exclude_from_thumbnail'] : array();
				
				$types_to_check = array_diff($post_types, $excludes);
				
				if (empty($types_to_check)) return;
				
				// checking for missing thumbnails
			
				if (isset($input['check_posts'])) :
					
					self::$options['empty_thumbs'] = array();
					
					foreach ($types_to_check as $post_type) :
					
						if (post_type_supports($post_type, 'thumbnail')) :
						
							self::$options['nothumb'][$post_type] = 0;
					
							foreach ($blogs as $id) :
						
								$results = $this->check_posts('thumbnail', $id, $post_type);
								
								self::$options['nothumb'][$post_type] += $results[0];
								
								if ($results[0] > 0) self::$options['empty_thumbs'][$post_type][$results[1]] = $results[2];
								
							endforeach;
							
							$type = (self::$options['nothumb'][$post_type] > 0) ? 'error' : 'updated';
							
							$nicename = $this->get_nicename($post_type);
							
							$message = sprintf(__('There have been %d %s without thumbnail detected.', 'automated-editing'), self::$options['nothumb'][$post_type], $nicename);
			
							add_settings_error('aed_options', 'aed-scanned-'.$post_type, $message, $type);
							
							if (self::$options['nothumb'][$post_type] == 0) unset(self::$options['nothumb'][$post_type]);
							
						endif;
						
					endforeach;
					
					if (!isset(self::$options['nothumb']) || empty(self::$options['nothumb'])) :
					
						$errors = get_settings_errors('aed_options');
					
						if (!$errors) :
						
							add_settings_error('aed_options', 'aed-scan-useless', __('There was nothing to scan.', 'automated-editing'), 'updated');
							
						endif;
					
					else :
					
						if (count(self::$options['nothumb']) == 0) unset(self::$options['nothumb']);
					
						if (count(self::$options['empty_thumbs']) == 0) unset(self::$options['empty_thumbs']);
					
					endif;
					
				endif;
				
				// try to repair missing thumbnails
				
				if (isset($input['fix_thumbs'])) :
				
					$list_to_fix = self::$options['empty_thumbs'];
					
					foreach ($list_to_fix as $post_type => $blogs) :
					
						$count = 0;
					
						foreach ($blogs as $blog => $items) :
						
							$results = $this->fix_posts('thumbnail', $post_type, $items);
							
							$count += $results[0];
							
							self::$options['nothumb'][$post_type] -= $results[0];
							
							self::$options['empty_thumbs'][$post_type][$blog] = $results[1];
							
							if (count(self::$options['empty_thumbs'][$post_type][$blog]) == 1) unset(self::$options['empty_thumbs'][$post_type][$blog]);
							
						endforeach;
						
						if (count(self::$options['empty_thumbs'][$post_type]) == 0) unset(self::$options['empty_thumbs'][$post_type]);
						
						if (self::$options['nothumb'][$post_type] == 0) unset(self::$options['nothumb'][$post_type]);
						
						$type = ($count > 0) ? 'updated' : 'error';
							
						$nicename = $this->get_nicename($post_type);
						
						$message = sprintf(__('%d %s have been fixed successfully.', 'automated-editing'), $count, $nicename);
						
						add_settings_error('aed_options', 'aed-fixed-'.$post_type, $message, $type);
						
					endforeach;
					
					if (count(self::$options['empty_thumbs']) == 0) unset(self::$options['empty_thumbs']);
					
					if (count(self::$options['nothumb']) == 0) unset(self::$options['nothumb']);
					
				endif;
				
				// change default thumbnail
				
				if (isset($input['change_thumbnail'])) :
				
					foreach ($types_to_check as $post_type) :
					
						if (post_type_supports($post_type, 'thumbnail')) :
						
							foreach ($blogs as $id) :
						
								if (function_exists('switch_to_blog')) switch_to_blog($blog_ID);
		
								$args = array (
									'posts_per_page' => -1,
									'post_type' => $post_type
								);
								
								$posts = get_posts($args);$posts = get_posts($args);
					
								$count = 0;
								
								foreach ($posts as $post) :
								
									$thumbnail_id = get_post_thumbnail_id( $post->ID );
									
									if ($thumbnail_id == self::$options['old_thumb_id']) :
									
										@set_post_thumbnail($post->ID, self::$options['thumbnail_id']);
										
										$count++;
										
									endif;
								
								endforeach;
								
								if (function_exists('restore_current_blog')) restore_current_blog();
								
							endforeach;
							
							$type = ($count > 0) ? 'updated' : 'error';
							
							$nicename = $this->get_nicename($post_type);
							
							$message = sprintf(__('%d featured images in %s have been changed successfully.', 'automated-editing'), $count, $nicename);
							
							add_settings_error('aed_options', 'aed-fixed-'.$post_type, $message, $type);
							
						endif;
						
					endforeach;
					
					self::$options['old_thumb_id'] = self::$options['thumbnail_id'];
					
				endif;
				
				break;	
		
		endswitch;
		
		return self::$options;
	
	}
	
	/**
	 *
	 * Adding Contextual Help Menus
	 *
	 */
	function add_help_text() {
		
		$screen = get_current_screen();
		
		//echo $screen->id; // use this to help determine $screen->id
		
		if ($screen->id == 'settings_page_automated-editing-settings' || $screen->id == 'toplevel_page_automated-editing-settings-network') :
		
			$content = self::tag_it(__('In the main Options, you can determine how you want to build the excerpt of the posts and whether or not to set the &#39;more&#39; tag after a certain sentence of the post.', 'automated-editing'), 'p');
			$content = self::tag_it(__('You can upload an image that will be used as the featured image for all posts without attachments.', 'automated-editing'), 'p');
			$content .= self::tag_it(__('You can check all your old posts for missing excerpts, &#39;more&#39; tags and thumbnails. If the plugin detects something, you&#39;ll be able to fix the posts automatically.', 'automated-editing'), 'p');
			$content .= self::tag_it(__('Some posts may contain images from outside or no image at all. There might not enough be text to build an excerpt or to set the &#39;more&#39; tag. Posts like that you will find back in the list with unfixed posts. You can just click on the headline and try editing the respective post(s) manually.', 'automated-editing'), 'p');
			$content .= self::tag_it(__('The list is not beautiful or something; it&#39;s just there at the moment.', 'automated-editing'), 'p');
			
			$screen->add_help_tab( array(
				'id'      => 'aed-general-help',
				'title'   => __('General'),
				'content' => $content,
			));
		
		endif;
		
	}
	
	/**
	 *
	 * Checking old posts for excerpts, more tags, thumbnails (and meta descriptions with Yoast WPSEO activated)
	 *
	 * @param $type (what to check for), $blog_ID, $post_type
	 *
	 */
	private function check_posts($type, $blog_ID, $post_type) {
		
		$blogurl = trailingslashit(get_home_url($blog_ID));
		
		if (function_exists('switch_to_blog')) switch_to_blog($blog_ID);
		
		$args = array (
			'posts_per_page' => -1,
			'post_type' => $post_type
		);
		
		$posts = get_posts($args);
					
		$list = array();
		
		foreach ($posts as $post) :
		
			$detect = false;
		
			if ($type == 'excerpt') :
			
				$excerpt = trim($post->post_excerpt);
		
				if (empty($excerpt)) $detect = true;
			
			endif;
			
			if ($type == 'metadesc') :
			
				$seo_desc = trim(get_post_meta($post->ID, '_yoast_wpseo_metadesc', true));
		
				if (empty($seo_desc)) $detect = true;
			
			endif;
			
			if ($type == 'tag') : 
		
				if (!strstr($post->post_content, '<!--more-->')) $detect = true;
			
			endif;
			
			if ($type == 'thumbnail') : 
		
				if (!has_post_thumbnail($post->ID)) $detect = true;
			
			endif;
			
			if (true === $detect) :
						
				$link = get_edit_post_link($post->ID);
				
				$list[] = array($link, $post->post_title, 'id' => $post->ID);
				
			endif;
			
		endforeach;
		
		if (function_exists('restore_current_blog')) restore_current_blog();
		
		$count = count($list);
		
		$list['blog_ID'] = (int) $blog_ID;
		
		return array($count, $blogurl, $list);
	}
	
	/**
	 *
	 * Fixing posts without excerpts, more tags, thumbnails (and meta descriptions with Yoast WPSEO activated)
	 *
	 * @param $type (what to fix), $post_type, $list (which posts to fix)
	 *
	 */
	private function fix_posts($type, $post_type, $list) {
		
		global $AutomatedEditing;
		
		$blog_ID = $list['blog_ID'];
		
		unset($list['blog_ID']);
		
		if (function_exists('switch_to_blog')) switch_to_blog($blog_ID);
		
		remove_action('wp_insert_post', array($AutomatedEditing, 'save_excerpt'), 300);
		remove_action('edit_attachment', array($AutomatedEditing, 'save_excerpt'), 300);
		remove_action('add_attachment', array($AutomatedEditing, 'save_excerpt'), 300);
					
		$count = 0;
		
		foreach ($list as $key => $fixit) :
		
		$check_post = get_post($fixit['id']);
		
			if ($type == 'excerpt') :
			
				$args = array(
					'content' => $check_post->post_content,
					'offset' => self::$options['offset'],
					'type' => self::$options['excerpt_style'],
					'count' => self::$options['excerpt_length'],
					'filter' => false,
					'shortcode' => false,
					'links' => false
				);

				$excerpt = strip_tags(A5_Excerpt::text($args));
				
				if (!empty($excerpt)) :
				
					$aed_post = array(
						'ID' => $check_post->ID,
						'post_excerpt' => $excerpt
					);
					
					@wp_update_post( $aed_post );
					
					unset ($list[$key]);
					
					$count++;
					
				endif;
			
			endif;
			
			if ($type == 'metadesc') :
			
				$args = array(
					'content' => $check_post->post_content,
					'offset' => self::$options['offset'],
					'type' => self::$options['excerpt_style'],
					'count' => self::$options['excerpt_length'],
					'filter' => false,
					'shortcode' => false,
					'links' => false
				);

				$seo_desc = strip_tags(A5_Excerpt::text($args));
				
				if (strlen($seo_desc) > 156) $seo_desc = trim(substr($seo_desc, 0, 154)).' &#8230;';
				
				if (!empty($seo_desc)) :
				
					@update_post_meta($check_post->ID, '_yoast_wpseo_metadesc', $seo_desc);
					
					unset ($list[$key]);
					
					$count++;
					
				endif;
			
			endif;
			
			if ($type == 'tag') : 
		
				$length = (!empty(self::$options['readmore_length'])) ? self::$options['readmore_length']*2 : 10;
			
				$short=array_slice(preg_split("/([\t.!?]\s+)/", $check_post->post_content, -1, PREG_SPLIT_DELIM_CAPTURE), 0, $length);
						
				$first_part = trim(implode($short));
				
				$second_part = substr($check_post->post_content, strlen($first_part));
				
				if (strlen($second_part) != 0) :
				
					$aed_post = array(
						'ID' => $check_post->ID,
						'post_content' => $first_part.'<!--more-->'.$second_part
					);
					
					@wp_update_post( $aed_post );
					
					unset ($list[$key]);
					
					$count++;
					
				endif;
			
			endif;
			
			if ($type == 'thumbnail') :
			
				$aed_args = array(
					'post_type' => 'attachment',
					'post_per_page' => 1,
					'post_status' => null,
					'post_parent' => $check_post->ID,
					'order' => 'ASC'
				);

				$aed_attachments = get_posts( $aed_args );
				
				if ($aed_attachments) : 
				
					$attachment_id = $aed_attachments[0]->ID;
					
				else :
				
					$attachment_id = $AutomatedEditing->check_for_images($check_post->ID);
				
				endif;
				
				if ( $attachment_id ) :
					
					@set_post_thumbnail($check_post->ID, $attachment_id);
					
					unset ($list[$key]);
				
					$count++;
					
				else :
				
					if (isset(self::$options['thumbnail_id'])) :
					
						@set_post_thumbnail($check_post->ID, self::$options['thumbnail_id']);
						
						unset ($list[$key]);
					
						$count++;
					
					endif;
						
				endif;
			
			endif;
			
		endforeach;
		
		add_action('wp_insert_post', array($AutomatedEditing, 'save_excerpt'), 300);
		add_action('edit_attachment', array($AutomatedEditing, 'save_excerpt'), 300);
		add_action('add_attachment', array($AutomatedEditing, 'save_excerpt'), 300); 
		
		if (function_exists('restore_current_blog')) restore_current_blog();
		
		$list['blog_ID'] = $blog_ID;
		
		return array($count, $list);

	}
	
	/**
	 *
	 * Getting ids of all blogs
	 *
	 */
	private function get_all_blog_ids() {
		
		global $wpdb;
		
		if (!is_plugin_active_for_network(AED_BASE)) :
		
			$blog_list = array(get_current_blog_id());
			
		else:
		
			$blogs = wp_get_sites();
			
			foreach ($blogs as $blog) $blog_list[] = $blog['blog_id'];
			
		endif;
		
		return $blog_list;
		
	}
	
	/**
	 *
	 * Printing the table with unfixed post( type)s
	 *
	 */
	private function do_the_table($table, $type, $id) {
		
		$eol = "\n";
		
		$tab = "\t";
		
		$dtab = $tab.$tab;
		
		foreach ($table as $post_type => $blog_list) :
		
			if (!empty($blog_list)) :
			
				$nicename = $this->get_nicename($post_type);
			
				echo self::open_postbox(sprintf(__('%s without %s', 'automated-editing'),$nicename, $type), $id.'-'.$post_type);
				
				foreach ($blog_list as $url => $list) :
				
					echo '<table class="widefat automated-editing-table">'.$eol.$tab;
					
					echo '<thead>'.$eol.$tab.'<tr>'.$eol.$dtab.'<th>'.$url.'</th>'.$eol.$tab.'</tr>'.$eol.$tab.'</thead>'.$eol.$tab.'<tbody>'.$eol;
					
					foreach ($list as $key => $item) :
						
						echo $dtab.'<tr>'.$eol.$dtab.$tab.'<td><a href="'.$item[0].'">'.$item[1].'</a></td>'.$eol.$dtab.'</tr>'.$eol;
						
					endforeach;
					
					echo $dtab.'</tbody>'.$eol.$tab.'</table>'.$eol.$tab;
					
				endforeach;
				
				echo self::close_postbox();
				
			endif;
			
		endforeach;
		
	}
	
	/**
	 *
	 * Getting post type nicename
	 *
	 */
	private function get_nicename($post_type) {
	
		$obj = get_post_type_object($post_type);
		
		return $obj->labels->name;
		
	}

} // class AED_Admin

?>