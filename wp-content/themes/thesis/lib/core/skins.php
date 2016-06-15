<?php
/*
Copyright 2012 DIYthemes, LLC. Patent pending. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/
*/
class thesis_skins {
	public $skin = array();
	public $installed = array();	// Skins in wp-content/thesis/skins/ array('class' => array('name', 'author', 'desc', 'path', 'class', 'folder'))
	public $updates = array();		// (array) available Skin updates
	public $active = false;
	public $preview = false;
	public static $headers = array(
		'name' => 'Name',
		'author' => 'Author',
		'description' => 'Description',
		'version' => 'Version',
		'class' => 'Class');

	public function __construct() {
		global $thesis;
		if ($thesis->environment == 'thesis' || $thesis->environment == 'admin' || $thesis->wp_customize) {
			$this->installed = $this->get_items();
			add_action('wp_loaded', array($this, 'updates'));
			if ($thesis->environment == 'admin' || $thesis->environment == 'thesis')
				add_filter('thesis_quicklaunch_menu', array($this, 'quicklaunch'), 30);
			if ($thesis->environment == 'admin') {
				new thesis_upload(array(
					'title' => __('Thesis Upload Skin', 'thesis'),
					'prefix' => 'thesis_skin_uploader',
					'file_type' => 'zip',
					'folder' => 'skin',
					'post_id' => 0));
				add_action('admin_post_thesis_skins', array($this, 'save'));
				add_action('admin_post_export_skin', array($this, 'export'));
			}
			if ($thesis->environment == 'thesis') {
				add_action('thesis_skin_menu', array($this, 'menu'), 100);
				if (!empty($_GET['canvas']) && $_GET['canvas'] == 'select_skin') {
					add_action('thesis_admin_canvas', array($this, 'canvas')); #wp
					add_action('admin_init', array($this, 'admin_init'));
					// separate action for writing CSS in multisite
					add_action('admin_init', array($this, 'maybe_rewrite_css'));
				}
			}
		}
		add_action('init', array($this, 'init'), 11);
		$this->skin();
		if ($thesis->environment == 'ajax') {
			add_action('wp_ajax_delete_skin', array($this, 'delete'));
			add_action('wp_ajax_backup_skin', array($this, 'backup'));
			add_action('wp_ajax_update_backup_skin_table', array($this, 'update_backup'));
			add_action('wp_ajax_restore_skin_backup', array($this, 'restore_backup'));
			add_action('wp_ajax_delete_skin_backup', array($this, 'delete_backup'));
			add_action('wp_ajax_restore_skin_default', array($this, 'restore_default'));
		}
	}

	public static function get_items() {
		$customize = !empty($GLOBALS['thesis']) && is_object($GLOBALS['thesis']) && $GLOBALS['thesis']->wp_customize ? true : false;
		$dir = is_dir(THESIS_USER_SKINS) ? THESIS_USER_SKINS : ($customize ? THESIS_SKINS : false);
		$skins = @scandir($dir);
		if (!is_array($skins)) # if this is happening, the pooch has been completely sodomized
			return false;
		$installed = array();
		foreach ($skins as $skin) {
			$skin_file = "$dir/$skin/skin.php";
			if ($skin == '.' || $skin == '..' || !@file_exists($skin_file)) continue;
			$file_data = get_file_data($skin_file, self::$headers); # skin.php is present
			$installed[$file_data['class']] = $file_data;
			$installed[$file_data['class']]['folder'] = $skin;
		}
		return $installed;
	}

	public function updates() {
		global $thesis;
		$this->updates = !empty($thesis->admin->updates['skins']) ? $thesis->admin->updates['skins'] : $this->updates;
	}

	public function quicklaunch($menu) {
		$add['skins'] = array(
			'text' => __('Manage Skins', 'thesis'). (!empty($this->updates) ? ' <span class="update-plugins"><span>'. count($this->updates). '</span></span>' : ''),
			'url' => 'admin.php?page=thesis&canvas=select_skin');
		return is_array($menu) ? array_merge($menu, $add) : $add;
	}

	public function menu($menu) {
		$add = array(
			'manage_break' => array(
				'text' => '––––––––––––',
				'url' => '#'),
			'manage' => array(
				'text' => __('Manage Skins', 'thesis'). (!empty($this->updates) ? ' <span class="count" title="'. __('Skin updates are available', 'thesis'). '">'. count($this->updates). '</span>' : ''),
				'url' => admin_url('admin.php?page=thesis&canvas=select_skin'),
				'description' => __('Change Skins, upload new Skins, or delete existing Skins', 'thesis')));
		return is_array($menu) ? array_merge($menu, $add) : $add;
	}

	public function admin_init() {
		global $thesis;
		wp_enqueue_style('thesis-options'); #wp
		wp_enqueue_style('thesis-skins', THESIS_CSS_URL. '/skins.css', array('thesis-options'), $thesis->version); #wp
		wp_enqueue_script('thesis-skins', THESIS_JS_URL. '/skins.js', array('thesis-menu'), $thesis->version, true); #wp
	}

	// TODO: See if Manager and associated functionality should be part of the active Skin object instead
	public function init() {
		$this->manager = new thesis_skin_manager($this->skin);
	}

	private function skin() {
		global $thesis;
		$this->active = apply_filters('_thesis_skin', $thesis->api->get_option('thesis_skin'));
		/*
		If installed AND active happen to be empty here, the logic goes to the else statement and then produces a useless Thesis installation
		because there is no active Skin and nothing is installed. We need a bailout that attempts to install the Classic Skin again if the
		/skins/classic directory still exists in the Thesis Core (which it does when this logic fails).
		Granted, this won't solve the problem if the issue is a server config, BUT when the user finally does fix the problem, this logic will
		force Thesis to work properly immediately and without further action from the user.
		*/
		if (empty($this->active) && !empty($this->installed)) // first install
			update_option('thesis_skin', ($skin = $this->installed['thesis_classic_r']));
		else {
			$skin = is_user_logged_in() && current_user_can('edit_theme_options') && ($this->preview = $thesis->api->get_option('thesis_skin_preview')) && !empty($this->preview) ?
				$this->preview :
				$this->active;
			if ((empty($skin['name']) && !empty($skin['class']) && !empty($this->installed[$skin['class']]))
			|| (in_array($thesis->environment, array('thesis', 'admin')) && !version_compare($this->installed[$skin['class']]['version'], $skin['version'], '=')))
				update_option('thesis_skin', ($skin = $this->installed[$skin['class']]));
		}
		if (!empty($skin)) { // will only evaluate to false prior to initial installation
			$this->skin = $skin;
			if (isset($this->skin['directory']) && !isset($this->skin['folder'])) // backwards compat
				$this->skin['folder'] = basename($this->skin['directory']);
			$dir = is_dir(THESIS_USER_SKINS) ? THESIS_USER_SKINS : THESIS_SKINS;
			$url_skins = is_dir(THESIS_USER_SKINS) ? THESIS_USER_SKINS_URL : THESIS_URL. '/lib/skins';
			$skin_file = "$dir/{$this->skin['folder']}/skin.php";
			if (@file_exists($skin_file)) {
				require_once($skin_file);
				define('THESIS_USER_SKIN', dirname($skin_file));
				define('THESIS_USER_SKIN_IMAGES', THESIS_USER_SKIN. '/images');
				define('THESIS_USER_SKIN_URL', $url_skins. '/'. $this->skin['folder']);
				define('THESIS_USER_SKIN_IMAGES_URL', THESIS_USER_SKIN_URL. '/images');
			}
		}
	}

	public function canvas() {
		global $thesis;
		$tab = str_repeat("\t", $depth = 2);
		$current = $preview = $installed = $current_updates = $installed_updates = '';
		if (is_array($this->installed) && !empty($this->installed))
			foreach ($this->installed as $class => $skin)
				if ($class == $this->active['class'])
					$current = $this->item_info($skin, true, false, $this->updates, $depth + 1);
				elseif ($class == $this->preview['class'])
					$preview = $this->item_info($skin, false, true, $this->updates, $depth + 1);
				else
					$installed .= $this->item_info($skin, false, false, $this->updates, $depth);
		if (isset($this->updates[$this->skin['class']])) {
			$current_updates = ' <span class="t_updates" title="'. __('An update is available for your current Skin.', 'thesis'). '">1</span>';
			unset($this->updates[$this->skin['class']]);
		}
		if (!empty($this->updates))
			$installed_updates = ' <span class="t_updates" title="'. __('Updates are available for your installed Skins.', 'thesis'). '">'. count($this->updates). '</span>';
		echo
			(!empty($_GET['changed']) && $_GET['changed'] == 'true' ?
			$thesis->api->alert(__('Success! You just changed your Thesis Skin.', 'thesis'), false, false, $depth) :
			(!empty($_GET['preview']) && $_GET['preview'] == 'true' ?
			$thesis->api->alert(__('You are now previewing a Skin in development mode. As an administrator, you can edit the Preview Skin, but visitors to your site will continue to see the Current Skin.', 'thesis'), false, false, $depth) :
			(!empty($_GET['stopped']) && $_GET['stopped'] == 'true' ?
			$thesis->api->alert(__('You are no longer previewing a Skin in development mode.', 'thesis'), false, false, $depth) :
			!empty($_GET['deleted']) && ($_GET['deleted'] == 'true' ?
			$thesis->api->alert(__('Skin deleted.', 'thesis'), false, false, $depth) :
			(!empty($preview) ?
			$thesis->api->alert(__('You are currently previewing a Skin in development mode. Visitors to your site will still see the Current Skin shown below, and you can develop the Preview Skin without fear of messing up your site for existing visitors!', 'thesis'), 'warning', false, $depth) : ''))))),
			(!empty($preview) ?
			"$tab<div class=\"active_skin preview_skin\">\n".
			"$tab\t<h3 id=\"preview_skin\">". __('Preview Skin', 'thesis'). "</h3>\n".
			$preview.
			"$tab</div>\n" : ''),
			"$tab<div class=\"active_skin\">\n",
			"$tab\t<h3 id=\"current_skin\">", __('Current Skin', 'thesis'), "$current_updates</h3>\n",
			$current,
			"$tab</div>\n",
			"$tab<span id=\"skin_upload\" data-style=\"button action\">", __('Upload a New Skin', 'thesis'), "</span>\n",
			"$tab<h3 id=\"installed_skins\">", __('Inactive Skins', 'thesis'), "$installed_updates</h3>\n",
			$installed,
			$thesis->api->popup(array(
				'id' => 'skin_uploader',
				'title' => __('Upload a Thesis Skin', 'thesis'),
				'body' => $thesis->api->uploader('thesis_skin_uploader')));
	}

	public static function item_info($skin, $active = false, $preview = false, $updates = array(), $depth = false) {
		global $thesis;
		if (!is_array($skin)) return;
		extract($skin); # name, author, description, version, class, folder
		if (empty($class) || empty($folder)) return;
		$tab = str_repeat("\t", (is_numeric($depth) ? $depth : 2));
		$screenshot = file_exists(trailingslashit(THESIS_USER_SKINS). "$folder/screenshot.png") ?
			trailingslashit(THESIS_USER_SKINS_URL). "$folder/screenshot.png" : (file_exists(trailingslashit(THESIS_USER_SKINS). "$folder/screenshot.jpg") ?
			trailingslashit(THESIS_USER_SKINS_URL). "$folder/screenshot.jpg" : false);
		$zip = ($active || $preview) && apply_filters('thesis_skin_create_zip', false) ?
			"$tab\t\t\t<a data-style=\"button action\" href=\"". wp_nonce_url(admin_url("update.php?action=thesis_generate_skin&skin=". esc_attr($class)), 'thesis-generate-skin'). "\">". __('Create Zip File', 'thesis'). "</a>\n" : false;
		$update = !empty($updates[$class]) && version_compare($updates[$class]['version'], $version, '>') ?
			" <a onclick=\"if(!thesis_update_message()) return false;\" data-style=\"button update\" href=\"". wp_nonce_url(admin_url('update.php?action=thesis_update_objects&type=skin&class='. esc_attr($class). '&name='. urlencode($thesis->api->escht($name))), 'thesis-update-objects'). '">'. sprintf(__('Update %s', 'thesis'), esc_attr($name)). '</a>' : '';
		return
			"$tab<div id=\"skin_". esc_attr($class). "\" class=\"skin_info\">\n".
			"$tab\t<form method=\"post\" action=\"". admin_url('admin-post.php?action=thesis_skins'). "\">\n".
			(!empty($screenshot) ?
			"$tab\t\t<img class=\"skin_screenshot\" src=\"$screenshot\" alt=\"". esc_attr($name). " screenshot\" width=\"300\" height=\"225\" />\n" : '').
			"$tab\t\t<h4>". $thesis->api->escht($name). " <span class=\"skin_version\">v ". esc_html($version). "</span> <span class=\"skin_by\">". __('by', 'thesis'). "</span> <span class=\"skin_author\">". esc_html($author). "</span></h4>\n".
			(!empty($update) ?
			"$tab\t\t<p>$update</p>\n" : '').
			"$tab\t\t<p>". $thesis->api->escht($description). "</p>\n".
			(($preview || ($active && !empty($zip)) || !($active || $preview)) ?
			"$tab\t\t<p>\n". (!($active || $preview) ?
			"$tab\t\t\t<input type=\"submit\" data-style=\"button action\" name=\"preview_skin\" value=\"". __('Preview Skin in Development Mode', 'thesis'). "\" />\n" : ($preview ?
			"$tab\t\t\t<input type=\"submit\" class=\"stop_preview\" data-style=\"button action\" name=\"stop_preview\" value=\"". __('Stop Previewing Skin', 'thesis'). "\" />\n" : '')).
			(!empty($zip) ? $zip : '').
			"$tab\t\t</p>\n" : '').
			(!$active ?
			"$tab\t\t<p>\n".
			"$tab\t\t\t<input type=\"hidden\" name=\"skin\" value=\"". esc_attr($class). "\" />\n".
			(!$preview ?
			"$tab\t\t\t<button data-style=\"button delete\" class=\"skin_delete\" data-class=\"$class\" data-name=\"". $thesis->api->escht($name). "\">". __('Delete Skin', 'thesis'). "</button>\n" : '').
			"$tab\t\t\t<input type=\"submit\" data-style=\"button save\" name=\"activate_skin\" value=\"". __('Activate Skin', 'thesis'). "\" />\n".
			"$tab\t\t</p>\n" : '').
			"$tab\t\t". wp_nonce_field('thesis-skins', '_wpnonce-thesis-skins', true, false). "\n". #wp
			"$tab\t</form>\n".
			"$tab</div>\n";
	}

	public function save() {
		global $thesis;
		$thesis->wp->check('edit_theme_options');
		check_admin_referer('thesis-skins', '_wpnonce-thesis-skins'); #wp
		if (!($class = $_POST['skin'])) {
			wp_redirect(admin_url('admin.php?page=thesis&canvas=select_skin&update=false')); #wp
			exit;
		}
		$this->get_items();
		if (!isset($this->installed[$class])) {
			wp_redirect(admin_url('admin.php?page=thesis&canvas=select_skin&update=false')); #wp
			exit;
		}
		if (@file_exists(THESIS_USER_SKINS. '/'. $this->installed[$class]['folder']. '/skin.php')) {
			if (!empty($_POST['preview_skin'])) {
				update_option('thesis_skin_preview', $this->installed[$class]); #wp
				wp_cache_flush(); #wp
				wp_redirect(admin_url('admin.php?page=thesis&canvas=select_skin&preview=true')); #wp
			}
			elseif (!empty($_POST['activate_skin'])) {
				delete_option('thesis_skin_preview');
				update_option('thesis_skin', $this->installed[$class]); #wp
				wp_cache_flush(); #wp
				wp_redirect(admin_url('admin.php?page=thesis&canvas=select_skin&changed=true&t_rewrite_css=yes')); #wp
			}
			elseif (!empty($_POST['stop_preview'])) {
				delete_option('thesis_skin_preview');
				wp_cache_flush(); #wp
				wp_redirect(admin_url('admin.php?page=thesis&canvas=select_skin&stopped=true')); #wp
			}
		}
		else
			wp_redirect(admin_url('admin.php?page=thesis&canvas=select_skin&update=false')); #wp
	}

	public function delete() {
		global $thesis;
		$thesis->wp->check('edit_theme_options');
		if (empty($_POST['class']) || empty($_POST['name'])) return;
		echo $thesis->api->popup(array(
			'id' => 'delete_'. esc_attr($_POST['class']),
			'title' => __('Delete Skin', 'thesis'),
			'body' =>
				"<iframe style=\"width:100%; height:100%;\" frameborder=\"0\" src=\"". wp_nonce_url(admin_url("update.php?action=thesis_delete_object&thesis_object_type=skin&thesis_object_class=". esc_attr($_POST['class']). "&thesis_object_name=". urlencode($_POST['name'])), 'thesis-delete-object'). "\" id=\"thesis_delete_". esc_attr($_POST['class']). "\"></iframe>\n"));
		if ($thesis->environment == 'ajax') die();
	}

	public function backup() {
		global $thesis;
		$thesis->wp->check('edit_theme_options');
		$thesis->wp->nonce($_POST['nonce'], 'thesis-skin-manager');
		echo $thesis->api->alert($this->manager->add($_POST['note']) === false ? __('Backup failed.', 'thesis') : __('Backup complete!', 'thesis'), 'manager_saved', true);
		if ($thesis->environment == 'ajax') die();
	}

	public function update_backup() {
		global $thesis;
		$thesis->wp->check('edit_theme_options');
		$thesis->wp->nonce($_POST['nonce'], 'thesis-skin-manager');
		echo $this->manager->backup_table();
		if ($thesis->environment == 'ajax') die();
	}

	public function restore_backup() {
		global $thesis;
		$thesis->wp->check('edit_theme_options');
		$thesis->wp->nonce($_POST['nonce'], 'thesis-skin-manager');
		if (!empty($_POST['id']) && $this->manager->restore((int) $_POST['id']))
			$this->css();
		if ($thesis->environment == 'ajax') die();
	}

	public function delete_backup() {
		global $thesis;
		$thesis->wp->check('edit_theme_options');
		$thesis->wp->nonce($_POST['nonce'], 'thesis-skin-manager');
		echo $thesis->api->alert($this->manager->delete((int) $_POST['id']) === false ? __('Deletion failed.', 'thesis') : __('Backup deleted!', 'thesis'), 'manager_saved', true);
		if ($thesis->environment == 'ajax') die();
	}

	public function export() {
		global $thesis;
		$thesis->wp->check('edit_theme_options');
		$thesis->wp->nonce($_POST['_wpnonce-thesis-skin-export'], 'thesis-skin-export');
		if (is_array($_POST['export'])) {
			$export = array_filter($_POST['export']);
			if (!empty($export))
				$this->manager->export($export);
		}
	}

	public function import($files, $action) {
		global $thesis;
		$thesis->wp->check('edit_theme_options');
		check_admin_referer($action, 'thesis_form_nonce');
		if ($this->manager->import($files)) {
			$this->css();
			return true;
		}
		return false;
	}

	public function restore_default() {
		global $thesis;
		$thesis->wp->check('edit_theme_options');
		$thesis->wp->nonce($_POST['nonce'], 'thesis-skin-manager');
		$form = array();
		if (!empty($_POST['form']))
			parse_str(stripslashes($_POST['form']), $form);
		echo $this->manager->defaults($form) === true ?
			'true' :
			$thesis->api->alert(__('Skin default not restored.', 'thesis'), 'manager_saved', true);
		if ($thesis->environment == 'ajax') die();
	}

	public function css() {
		global $thesis;
		require_once(THESIS_CORE. '/skin/css.php');
		$thesis->skin->_design_options();
		if (is_object($thesis->skin) && method_exists($thesis->skin, 'css_variables') && is_array($map = $thesis->skin->css_variables()) && is_array($vars = $thesis->skin->_css->update_vars($map)))
			update_option("{$this->skin['class']}_vars", $vars);
		$thesis->skin->_write_css();
	}

	public function maybe_rewrite_css() {
		global $thesis;
		if (is_multisite() && isset($_GET['page']) && $_GET['page'] == 'thesis' && isset($_GET['t_rewrite_css']))
			$thesis->skin->_write_css();
	}
}