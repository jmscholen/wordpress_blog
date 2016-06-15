<?php
/*
Copyright 2015 DIYthemes, LLC. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.

Version: 1.0
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/
Requires: Core Thesis object

About this class:
=============================
Even if the user selects a header image, the Skin developer must choose where to output this image
in the HTML. Some CSS may also be necessary to achieve the desired look.
*/
class thesis_skin_header_image {
	public $image = array();	// (array) array containing header image src, width, height, and id
	private $skin = false;		// (string) class name of the Skin employing this functionality
	private $name = false;		// (string) Skin name

	public function __construct() {
		global $thesis;
		$this->skin = !empty($thesis) && !empty($thesis->skins->skin['class']) ? $thesis->skins->skin['class'] : $this->skin;
		$this->name = !empty($thesis) && !empty($thesis->skins->skin['name']) ? "{$thesis->skins->skin['name']} " : $this->name;
		$this->image = is_array($image = $thesis->api->get_option("{$this->skin}__header_image")) ? $image : $this->image;
		if (empty($thesis->environment) || empty($this->skin) || empty($this->name)) return;
		// Add a Header Image item to the Thesis Skin and WordPress dashboard (quicklaunch) menus
		add_filter('thesis_skin_menu', array($this, 'menu'), 12);
		add_filter('thesis_quicklaunch_menu_skin', array($this, 'menu'), 12);
		// Add an options page to the Thesis Admin
		if (!empty($_GET['canvas']) && $_GET['canvas'] == "{$this->skin}__header_image") {
			add_action('admin_init', array($this, 'admin_init'));
			add_action('thesis_admin_canvas', array($this, 'admin'));
		}
		// Handle the saving of data on the newly-added options page
		add_action("admin_post_{$this->skin}_header_image", array($this, 'save'));
	}

	/*
	Filter method for adding an item to the WordPress Dashboard (quicklaunch) menu
	*/
	public function menu($menu) {
		$menu["{$this->skin}_header_image"] = array(
			'text' => __('Header Image', 'thesis'),
			'url' => "admin.php?page=thesis&canvas={$this->skin}__header_image",
			'description' => __('Add a Header Image to your design', 'thesis'));
		return $menu;
	}

	/*
	Initialize CSS, JS, and the WordPress Media Uploader for the header image admin page
	*/
	public function admin_init() {
		wp_enqueue_style('thesis-options');
		wp_enqueue_media();
		wp_enqueue_script('custom-header');
	}

	/*
	Header image admin page output
	*/
	public function admin() {
		global $thesis;
		$action = "{$this->skin}_header_image";
		echo
			"\t\t<h3>", sprintf(__('%1$sSkin Header Image', 'thesis'), $this->name), "</h3>\n",
			"\t\t<div class=\"option_item\" id=\"t_header_image_container\">\n",
			(!empty($this->image) ?
			"\t\t\t<img src=\"". esc_url($thesis->api->url_current($this->image['src'])). "\" height=\"". (int) $this->image['height']. "\" width=\"". (int) $this->image['width']. "\"/>\n".
			"\t\t\t<p style=\"font-size: 14px; color: #888;\">". sprintf(__('Current header image is %1$dpx wide by %2$dpx tall.', 'thesis'), (int) $this->image['width'], (int) $this->image['height']). "</p>\n" : ''),
			"\t\t</div>\n",
			"\t\t<p>\n",
			"\t\t<button id=\"choose-from-library-link\" data-style=\"save button\" data-update-link=\"", esc_url(add_query_arg(array('action' => $action, '_wpnonce' => wp_create_nonce($action)), admin_url("admin-post.php"))), "\" data-choose=\"", __('Select a Header Image', 'thesis'), "\" data-update=\"", __('Set Header Image', 'thesis'), "\">", __('Select Header Image', 'thesis'), "</button>\n",
			(!empty($this->image) ?
			"\t\t<a id=\"t_delete_header_image\" data-style=\"button delete inline\" href=\"". esc_url(add_query_arg(array('action' => $action, '_wpnonce' => wp_create_nonce($action), 'delete' => 'true'), admin_url("admin-post.php"))). "\">". __('Remove Header Image', 'thesis'). "</a>\n" : ''),
			"\t\t</p>\n";
	}

	/*
	Save the data from the header image admin page
	*/
	public function save() {
		global $thesis;
		$thesis->wp->check('edit_theme_options');
		$thesis->wp->nonce($_GET['_wpnonce'], "{$this->skin}_header_image");
		if (!empty($_GET['delete']) && $_GET['delete'] === 'true') {
			delete_option("{$this->skin}__header_image");
			$this->image = array();
		}
		else {
			$id = (int) $_GET['file'];
			$image = wp_get_attachment_metadata($id);
			update_option("{$this->skin}__header_image",
				$this->image = array(
					'src' => esc_url_raw(wp_get_attachment_url($id)),
					'height' => (int) $image['height'],
					'width' => (int) $image['width'],
					'id' => $id));
		}
		wp_cache_flush();
		$thesis->skin->_write_css();
		wp_redirect(admin_url("admin.php?page=thesis&canvas={$this->skin}__header_image"));
		exit;
	}

	/*
	Output the selected header image in a hook or filter location
	Note: It is up to the Skin developer to reference this method to output the header image
	*/
	public function html() {
		global $thesis;
		if (empty($this->image)) return;
		echo
			"<a id=\"thesis_header_image_link\" href=\"", esc_url(home_url()), "\"><img id=\"thesis_header_image\" src=\"", esc_url($thesis->api->url_current($this->image['src'])), "\" alt=\"", trim($thesis->api->escht((!empty($thesis->api->options['blogname']) ?
			htmlspecialchars_decode($thesis->api->options['blogname'], ENT_QUOTES). ' ' : ''). __('header image', 'thesis'))), "\" width=\"{$this->image['width']}\" height=\"{$this->image['height']}\" title=\"", __('click to return home', 'thesis'), "\" /></a>\n";
	}
}