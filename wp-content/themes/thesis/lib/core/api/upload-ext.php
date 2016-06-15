<?php
/*
Copyright 2012 DIYthemes, LLC. Patent pending. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/
*/
class thesis_uploader extends WP_Upgrader {
	public $default_headers = array(
		'name' => 'Name',
		'author' => 'Author',
		'description' => 'Description',
		'version' => 'Version',
		'class' => 'Class',
		'requires' => 'Requires');
	private $destination;

	public function install($package, $args, $upload_id) {
		// $type = skin, package or box
		if (!in_array($args['folder'], array('skin', 'package', 'box')))
			return new WP_Error('wrong_type', __('You have not given a recognized upload type.', 'thesis'));
		$this->strings = array(
			'unpack_package' => "Unpacking {$args['folder']}&#8230;",
			'process_success' => ucfirst($args['folder']). ' successfully installed&#8230;',
			'not_installed' => "{$args['folder']} was not installed&#8230;",
			'process_failed' => ucfirst($args['folder']). ' was not installed&#8230;');
		$this->args_t = $args;
		$this->upload_id = $upload_id;
		$this->init();

		// skin, box or package?
		$this->destination = $args['folder'] === 'skin' ? THESIS_USER_SKINS : ($args['folder'] === 'box' ? THESIS_USER_BOXES : ($args['folder'] === 'package' ? THESIS_USER_PACKAGES : false));
		// full path to asset
		$this->asset = $this->destination. '/'. basename($package, '.zip');
		// file type
		$this->type = !!$this->destination ? $args['folder'] : false;

		add_filter('upgrader_post_install', array($this, 'my_validate'), 10, 3);
		$this->run(array(
			'package' => $package,
			'destination' => $this->asset,
			'clear_destination' => false,
			'clear_working' => true));
		remove_filter('upgrader_post_install', array($this, 'my_validate'), 10, 3);
		if (!$this->result || is_wp_error($this->result))
			return $this->result;
		return true;
	}

	public function my_validate($true, $hook_extra, $result) {
		global $wp_filesystem, $thesis;
		// $result['remote_destination'] is the fs path to the installed folder and is trailingslashed
		$asset = untrailingslashit($result['remote_destination']);

		if (!$wp_filesystem->exists("$asset/{$this->type}.php"))
			return new WP_Error("no_{$this->type}", __("Could not find the {$this->type}.php file.", 'thesis'));

		// look for skin/box/package.php
		$file = "$asset/{$this->type}.php";

		// list the Skin contents
		if (! ($contents = $wp_filesystem->dirlist($asset)))
			$this->skin->feedback(__('The filesystem is currently unavailable.', 'thesis'));

		// get the headers
		$headers = get_file_data("{$result['local_destination']}/{$this->type}.php", $this->default_headers);

		// are we missing any crucial headers?
		if (empty($headers['class']) || empty($headers['version']) || empty($headers['name']))
			return new WP_Error('headers', sprintf(__('This %1$s has incomplete file headers. Please contact the author.', 'thesis'), $this->type));

		// check the requires header
		if (!empty($headers['requires']) && is_object($thesis) && !empty($thesis->version) && version_compare($thesis->version, $headers['requires'], '<')) {
			$wp_filesystem->delete($asset, true);
			return new WP_Error('requires', sprintf(__('This %1$s requires Thesis %2$s. You are running Thesis %3$s.', 'thesis'), $this->type, esc_attr($headers['requires']), esc_attr($thesis->version)));
		}

		$this->item_headers = $headers;
		$this->item_headers['folder'] = basename($result['local_destination']);
		add_action('admin_footer', array($this, 'admin_footer'), 100);

		if ($this->type === 'skin') {
			// do we have an images folder? if not, try to make it.
			if (!isset($f['images'])) // effectively checking wp_fs->exists without a func call
				if (!$wp_filesystem->is_dir("$asset/images") && !$wp_filesystem->mkdir("$asset/images"))
					$this->skin->feedback(__('Could not make images folder.', 'thesis'));
			// do we have a custom file? Doubtful. Let's make one.		
			if (!isset($f['custom.php']))
				if (!$wp_filesystem->put_contents("$asset/custom.php",
					"<?php\n".
					"/*\n".
					"This file is for Skin-specific customizations. Do not change your Skin’s skin.php file,\n".
					"as that will be upgraded in the future and your work will be lost.\n\n".
					"If you are comfortable with PHP, you can make more powerful customizations by using the\n".
					"Thesis Box system to create elements you can interact with in the Thesis HTML Editor.\n\n".
					"For more information, please visit: http://diythemes.com/thesis/rtfm/api/box/\n".
					"*/"))
					$this->skin->feedback(__('Could not make custom.php file.', 'thesis'));
		}
		$final_folder_name = basename($result['source']);
		$final_folder_parent = trailingslashit($wp_filesystem->find_folder(dirname($this->asset)));
		if (!$wp_filesystem->exists($final_folder_parent. $final_folder_name)) {
			// make the folder where this thing will live, move it and delete the empty folder -- all done with ->move(), thank the lort
			if (!$wp_filesystem->move($asset, $final_folder_parent. $final_folder_name))
				return new WP_Error('move_failure', sprintf(__('Unable to make the Skin directory at %1$s.', 'thesis'), $final_folder_parent. $final_folder_name));
		}
		return true;
	}

	public function admin_footer($complete = true) {
		global $thesis;
		if ($this->args_t['folder'] == 'skin') {
			$js = 'skins';
			$selector = '#installed_skins';
			$item = thesis_skins::item_info($this->item_headers);
		}
		else {
			$js = 'objects';
			$selector = '.object_list';
			$item = $this->args_t['folder'] == 'box' ?
				thesis_user_boxes::item_info($this->item_headers) : ($this->args_t['folder'] == 'package' ?
				thesis_user_packages::item_info($this->item_headers) : false);
		}
		$iframe = "#thesis_upload_iframe_{$this->args_t['prefix']}";
		$div = empty($GLOBALS['thesis_object_upload_fail']) ? "'#{$this->args_t['folder']}_{$this->item_headers['class']}'" : false;
		// final override
		$selector = empty($GLOBALS['thesis_object_upload_fail']) ? "'$selector'" : false;
		echo
			"<div style=\"display:none;\">$item</div>",
			"<script type=\"text/javascript\">\n",
			"(function(){\n",
			"\tparent.thesis_$js.add_item('$iframe', $div, $selector, '",
			admin_url("admin-post.php?action={$this->args_t['prefix']}_window&window_nonce=". wp_create_nonce('thesis_upload_iframe')), "');\n",
			"\tparent.thesis_$js.init();\n",
			"})();\n",
			"</script>\n";
	}

	public function fs_connect($directories = array(), $allow_relaxed_file_ownership = false) {
		global $wp_filesystem;
		$fs = create_function('$url, $e, $con', 'return request_filesystem_credentials($url, "", $e, $con);');
		$c = $fs($this->skin->options['url'], false, $this->skin->options['context']);
		$f = WP_Filesystem($c);

		if (!$c)
			return false;
		elseif (!$f) {
			$e = true;
			if (is_object($wp_filesystem) && $wp_filesystem->errors->get_error_code())
				$e = $wp_filesystem->errors;
			$fs($this->skin->options['url'], $e, $this->skin->options['context']); //Failed to connect, Error and request again
			return false;
		}
		elseif (!is_object($wp_filesystem))
			return new WP_Error('fs_unavailable', __('The filesystem is currently unavailable.', 'thesis'));

		// do we have a wp-content dir? if not, DIEEEEE	
		if (!$wp_filesystem->wp_content_dir())
			return new WP_Error('fs_no_content_dir', __('We could not find your /wp-content directory.', 'thesis'));

		// END wp_filesystem. Now, we work on more specific tasks.

		// folder in relation to wp_fs
		$asset_fs = trailingslashit($wp_filesystem->find_folder(dirname($this->asset))). trailingslashit(basename($this->asset));

		// list of installed assets we have by folder name.
		$installed = array_keys($wp_filesystem->dirlist($wp_filesystem->find_folder($this->destination), false));

		// attachment id of uploaded zip
		$id = !empty($this->upload_id) ? $this->upload_id : (!empty($_GET['object']) ? $_GET['object'] : false);

		// if the Skin we are trying to upload exists, we lose and it's time to bail.
		// do I need to delete anything (attachment, etc) here? I think so.
		if (in_array(basename($this->asset), $installed)) {
			if (!wp_delete_attachment(absint($id)))
				$this->skin->feedback('not_installed', __('We were unable to delete the .zip file from your server.', 'thesis'));
			$GLOBALS['thesis_object_upload_fail'] = true;
			return new WP_Error('skin_exists', sprintf( __('This %1$s already exists.', 'thesis'), esc_attr($this->args_t['folder'])));
		}

		// create the file destination we are uploading. Kinda dumb that I have to do this here.
		if (!$wp_filesystem->mkdir($asset_fs)) {
			if (!wp_delete_attachment(absint($id)))
				$this->skin->feedback('not_installed', __('We were unable to delete the .zip file from your server.', 'thesis'));
			return new WP_Error('mkdir_failure', sprintf(__('Unable to make %s.', 'thesis'), basename($this->asset)));
		}
		return true;
	}
}

class thesis_upload_skin extends WP_Upgrader_Skin {}