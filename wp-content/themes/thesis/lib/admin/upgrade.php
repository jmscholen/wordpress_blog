<?php
/*
Copyright 2015 DIYthemes, LLC. Patent pending. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/
*/
class thesis_upgrade {
	public function __construct() {
		add_action('after_switch_theme', array($this, 'upgrade'));
		add_action('update_option_theme_switched', array($this, 'redirect'), 10, 3);
		add_action('admin_post_thesis_fixes', array($this, 'fix'));
		if (!empty($_GET['canvas']) && $_GET['canvas'] == 'fix')
			add_action('thesis_admin_canvas', array($this, 'fix_admin'));
	}

	public function upgrade() {
		add_option('_thesis_did_db_upgrade', 1);
		if (get_option('_thesis_did_db_upgrade') === 1) {
			$this->meta();
			$this->terms();
			update_option('_thesis_did_db_upgrade', 0);
			wp_cache_flush();
		}
	}

	public function redirect($option) {
		if (strlen($option) > 0) {
			wp_redirect(admin_url('admin.php?page=thesis&upgraded=true')); #wp
			exit;
		}
	}

	/*
	Upgrade post meta from original Thesis data structure
	*/
	private function meta() {
		global $wpdb;
		$all_entries = array();
		$or = array(
			'thesis_title' => array(
				'meta' => 'thesis_title_tag',
				'field' => 'title'),
			'thesis_description' => array(
				'meta' => 'thesis_meta_description',
				'field' => 'description'),
			'thesis_keywords' => array(
				'meta' => 'thesis_meta_keywords',
				'field' => 'keywords'),
			'thesis_robots' => array(
				'meta' => 'thesis_meta_robots',
				'field' => 'robots'),
			'thesis_canonical' => array(
				'meta' => 'thesis_canonical_link',
				'field' => 'url'),
			'thesis_slug' => array(
				'meta' => 'thesis_html_body',
				'field' => 'class'),
			'thesis_readmore' => array(
				'meta' => 'thesis_post_content',
				'field' => 'read_more'),
			'thesis_post_image' => array(
				'meta' => 'thesis_post_image',
				'field' => 'image',
				'additional' => 'url'),
			'thesis_post_image_alt'	 => array(
				'meta' => 'thesis_post_image',
				'field' => 'alt'),
			'thesis_post_image_frame' => array(
				'meta' => 'thesis_post_image',
				'field' => 'frame',
				'additional' => 'on'),
			'thesis_post_image_horizontal' => array(
				'meta' => 'thesis_post_image',
				'field' => 'alignment'),
			'thesis_thumb' => array(
				'meta' => 'thesis_post_thumbnail',
				'field' => 'image',
				'additional' => 'url'),
			'thesis_thumb_frame' => array(
				'meta' => 'thesis_post_thumbnail',
				'field' => 'frame',
				'additional' => 'on'),
			'thesis_thumb_alt' => array(
				'meta' => 'thesis_post_thumbnail',
				'field' => 'alt'),
			'thesis_thumb_horizontal' => array(
				'meta' => 'thesis_post_thumbnail',
				'field' => 'alignment'),
			'thesis_redirect' => array(
				'meta' => 'thesis_redirect',
				'field' => 'url'));
		$ors = implode("' OR meta_key = '", array_keys($or));
		$metas = (array) $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = '$ors'");
		if (!!! $metas)
			return;
		$new_sorted = array();
		foreach ($metas as $results) {
			$results = (array) $results;
			if (isset($or[$results['meta_key']]['additional']))
				$new_sorted[$results['post_id']][$or[$results['meta_key']]['meta']][$or[$results['meta_key']]['field']][$or[$results['meta_key']]['additional']] = maybe_unserialize($results['meta_value']);
			else
				$new_sorted[$results['post_id']][$or[$results['meta_key']]['meta']][$or[$results['meta_key']]['field']] = maybe_unserialize($results['meta_value']);
		}		
		foreach ($new_sorted as $id => $meta_keys)
			foreach ($meta_keys as $meta_key => $save)
				update_post_meta($id, "_$meta_key", $save);
	}

	/*
	Upgrade term metadata to new db structure
	*/
	public function terms() {
		global $wpdb; #wp
		$table = $wpdb->prefix. 'thesis_terms';
		if (! $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE '%s'", $table))) return;
		$whats = array(
			'title' => array(
				'class' => 'thesis_title_tag',
				'field' => 'title'),
			'description' => array(
				'class' => 'thesis_meta_description',
				'field' => 'description'),
			'keywords' => array(
				'class' => 'thesis_meta_keywords',
				'field' => 'keywords'),
			'robots' => array(
				'class' => 'thesis_meta_robots',
				'field' => 'robots'),
			'headline' => array(
				'class' => 'thesis_archive_title',
				'field' => 'title'),
			'content' => array(
				'class' => 'thesis_archive_content',
				'field' => 'content'));
		$sql = implode(',', array_keys($whats));
		$terms = $wpdb->get_results("SELECT term_id,$sql FROM $table", ARRAY_A); #wp
		if (empty($terms)) return;
		$new = array();
		foreach ($terms as $data) {
			$id = array_shift($data);
			foreach ($data as $column => $value)
				if (!empty($value))
					$new[$id][$whats[$column]['class']][$whats[$column]['field']] = maybe_unserialize($value);
		}
		if (!empty($new) && is_array($new))
			update_option('thesis_terms', $new);
	}

	/*---:[ special admin page to force data fixes in the event that upgrade-based data migration fails ]:---*/

	public function fix_admin() {
		global $thesis;
		$post_meta = $thesis->api->form->fields(array(
			'post_meta' => array(
				'type' => 'checkbox',
				'title' => __('Rebuild Post Meta', 'thesis'),
				'options' => array(
					'rebuild' => __('Run the post meta upgrade routine (please note: this may take a few minutes)', 'thesis')))), false, 't_post_meta_', false, 3, 3);
		echo
			"\t\t<h3>", __('Thesis Fixes', 'thesis'), "</h3>\n",
			"\t\t<form method=\"post\" action=\"", esc_url(admin_url('admin-post.php?action=thesis_fixes')), "\" id=\"t_fixes\">\n",
			"\t\t\t{$post_meta['output']}\n",
			"\t\t\t<input type=\"submit\" data-style=\"button action\" id=\"do_actions\" name=\"do_actions\" value=\"", __('Complete Selected Fixes', 'thesis'), "\" />\n",
			wp_nonce_field('thesis_fixes', '_wpnonce', true, false),
			"\t\t</form>\n";
	}

	public function fix() {
		global $thesis;
		check_admin_referer('thesis_fixes');
		$thesis->wp->check('edit_theme_options');
		$this->meta();
		$this->terms();
		wp_cache_flush();
		wp_redirect(add_query_arg(array('success' => 'true'), wp_get_referer()));
		exit;
	}
}