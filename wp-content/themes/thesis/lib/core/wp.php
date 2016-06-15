<?php
/*
Copyright 2012 DIYthemes, LLC. Patent pending. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/
*/
class thesis_wp {
	public $widgets = array(			// (array) core Thesis widget classes
		'thesis_search_widget',
		'thesis_widget_google_cse',
		'thesis_killer_recent_entries');
	public $boxes = array(				// (array) Boxes this controller is adding to Thesis
		'head' => array(
			'thesis_feed_link',
			'thesis_pingback_link',
			'thesis_rel_next_prev'),
		'core' => array(
			'thesis_wp_nav_menu',
			'thesis_wp_loop',
			'thesis_comments_intro',
			'thesis_comments_nav',
			'thesis_comments',
			'thesis_comment_form',
			'thesis_trackbacks',
			'thesis_previous_post_link',
			'thesis_next_post_link',
			'thesis_previous_posts_link',
			'thesis_next_posts_link',
			'thesis_wp_widgets',
			'thesis_wp_admin'),
		'post_box' => array(
			'thesis_wp_featured_image'));
	public $dependents = array(
		'thesis_post_box',
		'thesis_post_list',
		'thesis_query_box');

	public function __construct() {
		define('THESIS_WP', THESIS_CORE. '/wp');
		require_once(THESIS_WP. '/boxes.php');
		require_once(THESIS_WP. '/terms.php');
		require_once(THESIS_WP. '/widgets.php');
		$this->actions();
		$this->filters();
		$this->widgets();
		$terms = new thesis_terms;
		$this->terms = $terms->terms;
		new thesis_dashboard_rss;
	}

	private function actions() {
		load_theme_textdomain('thesis', THESIS_USER. '/languages');
		add_theme_support('menus');
		add_action('widgets_init', array($this, 'init'));
		add_action('wp_loaded', array($this, 'modify_front_end'));
		add_action('thesis_boxes', array($this, 'core_boxes'));
		foreach ($this->dependents as $box)
			add_action("{$box}_dependents", array($this, 'post_boxes'));
		remove_action('wp_head', 'wp_generator');
		remove_action('wp_head', 'start_post_rel_link');
		remove_action('wp_head', 'index_rel_link');
		remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
		remove_action('wp_head', 'parent_post_rel_link');
		remove_action('wp_head', 'rel_canonical');
		remove_action('wp_head', 'wp_shortlink_wp_head');
		remove_action('wp_head', 'rsd_link');
		remove_action('wp_head', 'wlwmanifest_link');
		add_action('hook_head', 'wp_head');
		add_action('hook_after_html', 'wp_footer', 1);
	}

	private function filters() {
		add_filter('post_class', array($this, 'post_class'));
		$capital_P = array(
			'the_content',
			'the_title',
			'comment_text');
		foreach ($capital_P as $dangit)
			remove_filter($dangit, 'capital_P_dangit'); # Dagnabbit.
	}

	private function widgets() {
		if (is_array($this->widgets))
			foreach ($this->widgets as $widget)
				register_widget($widget);
	}

	public function init() {
		global $pagenow; #wp
		// Initiate Boxes whose $type = false
		if (is_array($boxes = apply_filters('thesis_wp_boxes_head', $this->boxes['head'])))
			foreach ($boxes as $box)
				new $box;
		// Post meta
		if (is_admin() && in_array($pagenow, array('post.php', 'page.php', 'post-new.php'))) {
			add_action('init', array($this, 'init_post_meta'), 11);
			wp_enqueue_style('thesis-edit', THESIS_CSS_URL. '/edit.css'); #wp
			wp_enqueue_script('jquery-ui-core'); #wp
			wp_enqueue_script('thesis-edit', THESIS_JS_URL. '/edit.js'); #wp
		}
		// Miscellaneous, timing-dependent functionality
		if (!apply_filters('thesis_emoji', true)) {
			remove_action('wp_head', 'print_emoji_detection_script', 7);
			remove_action('admin_print_scripts', 'print_emoji_detection_script');
			remove_action('wp_print_styles', 'print_emoji_styles');
			remove_action('admin_print_styles', 'print_emoji_styles');
		}
	}

	public function core_boxes($boxes) {
		if (is_array($boxes) && is_array($core = apply_filters('thesis_wp_boxes_core', $this->boxes['core'])))
			$boxes = array_merge($boxes, $core);
		return $boxes;
	}

	public function post_boxes($boxes) {
		if (is_array($boxes) && is_array($post_box = apply_filters('thesis_wp_boxes_post_box', $this->boxes['post_box'])))
			$boxes = array_merge($boxes, $post_box);
		return $boxes;
	}

	public function init_post_meta() {
		require_once(THESIS_WP. '/post_meta.php');
		$this->post_meta = apply_filters('thesis_post_meta', array());
		$tabindex = 60;
		if (!is_array($this->post_meta)) return;
		foreach ($this->post_meta as $class => $meta) {
			new thesis_post_meta($class, $meta, $tabindex);
			$tabindex = $tabindex + 30;
		}
	}

/*---:[ Filters ]:---*/

	public function post_class($classes) {
		unset($classes[array_search('hentry', $classes)]);
		return $classes;
	}

/*---:[ Modifiers ]:---*/

	public function modify_front_end() {
		if (!apply_filters('thesis_emoji', true)) {
			remove_action('wp_head', 'print_emoji_detection_script', 7);
			remove_action('admin_print_scripts', 'print_emoji_detection_script');
			remove_action('wp_print_styles', 'print_emoji_styles');
			remove_action('admin_print_styles', 'print_emoji_styles');
		}
	}

/*---:[ Thesis WP API methods ]:---*/

	/*
	Apply the specified $filters to $content.
	— $content: the content to filter
	— $filters: an array of filters with filter function => priority pairs
	*/
	public function filter($content, $filters = array()) {
		if (empty($filters) || !is_array($filters)) return;
		foreach ($filters as $filter => $priority)
			if (!empty($priority) && is_numeric($priority))
				add_filter($content, $filter, $priority);
			else
				add_filter($content, $filter);
	}

	/*
	Use $thesis->wp->check() to see if the current user has permission to access certain functionality within WP.
	*/
	public function check($access = false) {
		$access = $access ? $access : 'edit_theme_options';
		if (!current_user_can($access))
			wp_die(__('Easy there, homeh. You don&#8217;t have admin privileges to change Thesis settings.', 'thesis'));
	}

	/*
	Use $thesis->wp->nonce() to perform a nonce check for enhanced interface security.
	*/
	public function nonce($nonce, $action, $passthrough = false) {
		if (!$nonce || !$action)
			die(__('Your nonce check is incorrect. Check the nonce name and action and try again.', 'thesis'));
		if (!wp_verify_nonce($nonce, $action))
			if (!!$passthrough)
				return false;
			else
				die(__('It looks like you may have been logged out of WordPress. Log back in, and then try your action again.', 'thesis'));
		if (!!$passthrough)
			return true;
	}

	public function author($author_id, $field = false) { // fields: ID, user_login, user_nicename, display_name, user_email, user_url, user_registered, user_status
		if (!$author_id) return;
		$author = get_userdata($author_id); #wp
		return $field ? $author->data->$field : $author->data;
	}

	public function language_attributes() {
		$attributes = array();
		if ($dir = is_rtl() ? 'rtl' : 'ltr') #wp
			$attributes[] = "dir=\"$dir\"";
		if ($lang = get_bloginfo('language')) #wp
			$attributes[] = "lang=\"$lang\"";
		$attributes = !empty($attributes) ? ' '. implode(' ', $attributes) : '';
		return apply_filters('thesis_language_attributes', $attributes);
	}
}