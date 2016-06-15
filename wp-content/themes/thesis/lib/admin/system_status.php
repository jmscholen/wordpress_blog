<?php
/*
Copyright 2015 DIYthemes, LLC. Patent pending. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/
*/
class thesis_system_status {
	public function __construct() {
		add_filter('thesis_quicklaunch_menu', array($this, 'menu_break'), 99);
		add_filter('thesis_quicklaunch_menu', array($this, 'menu'), 100);
		add_filter('thesis_more_menu', array($this, 'menu'), 2);
		if (!empty($_GET['canvas']) && $_GET['canvas'] == 'system_status') {
			add_action('thesis_admin_canvas', array($this, 'admin'));
			add_action('admin_footer', array($this, 'js'));
		}
	}

	public function menu_break($menu) {
		$menu['break_system_status'] = array(
			'text' => '––––––––––––',
			'url' => '#');
		return $menu;
	}

	public function menu($menu) {
		$menu['system_status'] = array(
			'text' => __('System Status', 'thesis'),
			'url' => 'admin.php?page=thesis&canvas=system_status',
			'title' => __('Check your system for compatibility with Thesis.', 'thesis'));
		return $menu;
	}

	/*
	System Status admin page
	*/
	public function admin() {
		global $thesis, $wp_version, $wpdb;
		include_once(ABSPATH. '/wp-admin/includes/file.php');
		$table_status = $wpdb->get_results($wpdb->prepare("SHOW TABLE STATUS FROM ". $wpdb->dbname. " LIKE '%s'", $wpdb->options));
        if (empty($wpdb->use_mysqli))
            $ver = mysql_get_server_info();
        else
            $ver = mysqli_get_server_info($wpdb->dbh);
		$plugins = array();
		foreach (get_plugins() as $plugin)
			$plugins[] = esc_attr($plugin['Name']);
		echo
			"\t\t<h3>", __('System Status', 'thesis'), "</h3>\n",
			"\t\t<div class=\"option_item option_field\">\n",
			"\t\t\t<p>\n",
			"\t\t\t\t<textarea id=\"t_system_status\" rows=\"25\">\n",
			__('About Thesis', 'thesis'), "\n",
			"===========================\n",
			sprintf(__('Thesis Version: %s', 'thesis'), esc_attr($thesis->version)), "\n",
			sprintf(__('Current Skin Name: %s', 'thesis'), esc_attr($thesis->skins->skin['name'])), "\n",
			sprintf(__('Current Skin Version: %s', 'thesis'), esc_attr($thesis->skins->skin['version'])), "\n",
			sprintf(__('Current Skin Author: %s', 'thesis'), esc_attr($thesis->skins->skin['author'])), "\n",
			(!empty($thesis->skins->skin['requires']) ? sprintf(__('Current Skin Requires: %s', 'thesis'), esc_attr($thesis->skins->skin['requires'])). "\n\n" : "\n"),
			__('Thesis Filesystem Check', 'thesis'), "\n",
			"===========================\n",
			"wp-content/thesis: ", (is_dir(WP_CONTENT_DIR. '/thesis') ? 'YES' : 'NO'), "\n",
			"wp-content/thesis/skins: ", (is_dir(WP_CONTENT_DIR. '/thesis/skins') ? 'YES' : 'NO'), "\n",
			"wp-content/thesis/boxes: ", (is_dir(WP_CONTENT_DIR. '/thesis/boxes') ? 'YES' : 'NO'), "\n",
			"wp-content/thesis/master.php: ", (is_file(WP_CONTENT_DIR. '/thesis/master.php') ? 'YES' : 'NO'), "\n",
			__('Skin CSS Writable: ', 'thesis'), (!is_file(THESIS_USER_SKIN. '/css.css') ? __('No Skin CSS File', 'thesis') : (is_writable(THESIS_USER_SKIN. '/css.css') ? 'YES' : 'NO')), "\n\n",
			__('About WordPress', 'thesis'), "\n",
			"===========================\n",
			sprintf(__('WordPress Version: %s', 'thesis'), esc_attr($wp_version)), "\n",
			sprintf(__('Filesystem Method: %s', 'thesis'), get_filesystem_method()), "\n",
			sprintf(__('Using Multisite: %s', 'thesis'), (is_multisite() ? 'YES' : 'NO')), "\n",
			sprintf(__('Installed Plugins: %s', 'thesis'), implode(', ', $plugins)), "\n\n",
			__('About PHP', 'thesis'), "\n",
			"===========================\n",
			sprintf(__('Version: %s', 'thesis'), PHP_VERSION), "\n",
			sprintf(__('cURL: %s', 'thesis'), (function_exists('curl_init') ? 'YES' : 'NO')), "\n",
			sprintf(__('Max Upload (according to WP): %s', 'thesis'), size_format(wp_max_upload_size())), "\n",
			sprintf(__('Memory Limit (ini): %s', 'thesis'), esc_attr(ini_get('memory_limit'))), "\n",
			(function_exists('memory_get_usage') ?
				sprintf(__('Memory Limit (usage): %s', 'thesis'), size_format(memory_get_usage(true))) : ''), "\n",
			(defined('WP_MEMORY_LIMIT') ?
				sprintf(__('Memory Limit (WP): %s', 'thesis'), WP_MEMORY_LIMIT) : ''), "\n\n",
			__('About Server/Database', 'thesis'), "\n",
			"===========================\n",
			sprintf(__('Site URL: %s', 'thesis'), esc_url(get_site_url())), "\n",
			sprintf(__('Server Software: %s', 'thesis'), esc_attr($_SERVER['SERVER_SOFTWARE'])), "\n",
			sprintf(__('Options Collation: %s', 'thesis'), esc_attr($table_status[0]->Collation)), "\n",
			sprintf(__('MySQL Version: %s', 'thesis'), esc_attr($ver)), "\n",
			sprintf(__('PHP Handler: %s', 'thesis'), esc_attr((function_exists('php_sapi_name') ? php_sapi_name() : 'Unknown'))), "\n\n",
			__('About Browser', 'thesis'), "\n",
			"===========================\n",
			sprintf(__('User Agent: %s', 'thesis'), !empty($_SERVER['HTTP_USER_AGENT']) ? esc_attr($_SERVER['HTTP_USER_AGENT']) : __('None reported.', 'thesis')),
			"</textarea>\n",
			"\t\t\t</p>\n",
			"\t\t</div>\n";
	}

	/*
	Script to enable one-click highlighting for easy copy/paste of system status data
	*/
	public function js() {
		echo
			"\t\t<script type=\"text/javascript\">\n",
			"\t\t\tjQuery(document).ready(function($){\n",
			"\t\t\t\tjQuery('#t_system_status').focus(function(){\n",
			"\t\t\t\t\tvar \$this = jQuery(this);\n",
			"\t\t\t\t\t\$this.select();\n",
			"\t\t\t\t\t\$this.mouseup(function() {\n",
			"\t\t\t\t\t\t\$this.unbind(\"mouseup\");\n",
			"\t\t\t\t\t\treturn false;\n",
			"\t\t\t\t\t});\n",
			"\t\t\t\t});\n",
			"\t\t\t});\n",
			"\t\t</script>\n";
	}
}