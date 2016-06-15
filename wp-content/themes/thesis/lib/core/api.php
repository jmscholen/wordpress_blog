<?php
/*
Copyright 2012 DIYthemes, LLC. Patent pending. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/
*/
class thesis_api {
	public $boxes = array(
		'thesis_tracking_scripts',
		'thesis_meta_verify',
		'thesis_google_analytics',
		'thesis_google_publisher',
		'thesis_404');

	public function __construct() {
		global $thesis;
		define('THESIS_API', THESIS_CORE. '/api');
		require_once(THESIS_API. '/colors.php');
		require_once(THESIS_API. '/css.php');
		require_once(THESIS_API. '/fonts.php');
		require_once(THESIS_API. '/form.php');
		require_once(THESIS_API. '/schema.php');
		require_once(THESIS_API. '/typography.php');
		require_once(THESIS_API. '/upload.php');
		$this->options = wp_load_alloptions();
		$this->strings = $this->strings();
		$this->form = new thesis_form_api;
		$this->schema = new thesis_schema;
		$this->colors = new thesis_colors;
		$this->fonts = new thesis_fonts;
		$this->typography = new thesis_typography;
		$this->css = new thesis_css_api;
		$this->deprecated_hooks();
		add_action('widgets_init', array($this, 'boxes'));
		if (is_multisite() && is_admin() && !empty($_GET['action']) && $_GET['action'] == 'thesis_do_css')
			add_action('init', array($this, 'ms_css'));
	}

	/*
	The following two methods add Boxes that accommodate Third Party functionality (and thus cannot be considered "core").
	*/
	public function boxes() {
		// TODO: Remove thesis_google_publisher if WPSEO Plugin is detected
		require_once(THESIS_API. '/boxes.php');
		foreach ($this->boxes as $box)
			new $box;
		// Activate thesis_home_seo as an accessible API element
		$this->home_seo = new thesis_home_seo;
		// Include Twitter Profile Box
		foreach (array('thesis_post_box', 'thesis_post_list', 'thesis_query_box') as $box)
			add_action("{$box}_dependents", array($this, 'add_thesis_twitter_profile'));
	}

	public function add_thesis_twitter_profile($dependents) {
		$dependents[] = 'thesis_twitter_profile';
		return $dependents;
	}

/*---:[ Begin Thesis API methods ]:---*/

/*---:[ Custom hook system ]:---*/

	/*
	Custom hook gateway system that allows for easy hook-sniffing. Wherever you wish to include a hook in your code,
	reference $thesis->api->hook() and provide one or both of the following:
	— $hook: (required) the unique name of the hook
	— $args: (optional) arguments to pass to hooked functions

	Usage example: $thesis->api->hook('my_special_hook');
	*/
	public function hook($hook, $args = '') {
		if (empty($hook)) return;
		$this->hooks[] = $hook;
		return do_action($hook, $args);
	}

/*---:[ Text escaping methods ]:---*/

	/*
	$thesis->api->esc() should be used when escaping a value for output inside an HTML attribute (such as in a form)
	— $value: the value to be escaped
	*/
	public function esc($value) {
		return esc_attr(stripslashes($value));
	}

	/*
	$thesis->api->esch() should be used when escaping HTML that appears between tags
	— $value: the text to be escaped
	*/
	public function esch($value) {
		return esc_html(stripslashes($value));
	}

	/*
	$thesis->api->escht() should be used when escaping HTML that must convert certain characters into equivalent HTML entities
	— $value: the text to be escaped
	— $strip: whether or not to perform stripslashes() on the $value (typically not desirable for this sort of escaping)
	*/
	public function escht($value, $strip = false) {
		return esc_html(wptexturize($strip ? stripslashes($value) : $value));
	}

	/*
	Check which Thesis template is currently running.
	Note: This method uses the proper template name (such as "Landing Page")
	*/
	public function is_template($name = '') {
		global $thesis;
		if (!empty($name) && !empty($thesis->skin->_template['title']) && $thesis->skin->_template['title'] === $name)
			return true;
	}

/*---:[ Data handling methods ]:---*/

	/*
	Using $thesis->api->get_option() to access your options (instead of the native WP method)
	reduces the total number of database calls required in any given page load.
	— $option: the name of the option to retrieve (this can be any option stored in the wp_options table)
	*/
	public function get_option($option) {
		return isset($this->options[$option]) ? maybe_unserialize($this->options[$option]) : false;
	}

	/*
	The set_options method takes data from a form and returns an array of vaules ready to be stored in a database.
	— $fields: an array of options in Thesis Options API Array Format (http://diythemes.com/thesis/rtfm/api/options/array-format/)
	— $values: an array of values from a form
	— $reference: the 'image' field requires a reference parameter to locate relevant data
	— $upload_type: the 'image' field may require an upload type parameter to function properly
	— $post_id: the 'image' field may require a post ID parameter to function properly
	*/
	public function set_options($fields, $values, $reference = '', $upload_type = 'default', $post_id = 0) {
		if (!is_array($fields)) return false;
		$save = array();
		foreach ($fields as $id => $field) {
			if (is_array($field)) {
				if ($field['type'] == 'group') {
					if (is_array($field['fields']))
						if ($group = $this->set_options($field['fields'], $values))
							foreach ($group as $item_id => $val)
								$save[$item_id] = $val;
				}
				elseif ($field['type'] == 'object_set') {
					if (is_array($field['objects']))
						foreach ($field['objects'] as $name => $object)
							if (is_array($object['fields']))
								if ($object_values = $this->set_options($object['fields'], !empty($values[$name]) ? $values[$name] : array()))
									foreach ($object_values as $obj_id => $val)
										$save[$name][$obj_id] = $val;
				}
				elseif ($field['type'] == 'custom') {
					if (is_array($field['options']))
						foreach ($field['options'] as $custom_id => $default)
							if (!empty($values[$custom_id]) && $values[$custom_id] != $default)
								$save[$custom_id] = $values[$custom_id];
				}
				else {
					if ($field['type'] == 'image') {
						$value = !empty($values[$id]) && is_array($values[$id]) ? array_filter($values[$id]) : false;
						if (!empty($_FILES["{$reference}$id"]['name'])) {
							$new_image = $this->save_image("{$reference}$id", $upload_type, $post_id);
							$diff = array_diff(array('url', 'width', 'height', 'id'), array_keys(array_filter($new_image)));
							if ((in_array($upload_type, array('default', 'box')) && empty($diff))
								|| ($upload_type == 'skin' && count($diff) === 3)) {
								$value['url'] = esc_url_raw($this->url_relative($new_image['url']));
								$value['width'] = absint($new_image['width']);
								$value['height'] = absint($new_image['height']);
								if (isset($new_image['id']))
									$value['id'] = (int) $new_image['id'];
							}
						}
					}
					else {
						$value = !empty($values[$id]) ? $values[$id] : false;
						if ($field['type'] == 'checkbox' && is_array($field['options'])) {
							$checkbox = array();
							$value = is_array($value) ? $value : array();
							foreach ($field['options'] as $option => $label)
								if (!empty($value[$option]) && empty($field['default'][$option]))
									$checkbox[$option] = true;
								elseif (isset($value[$option]) && empty($value[$option]) && !empty($field['default'][$option]))
									$checkbox[$option] = false;
							if (!empty($checkbox))
								$value = $checkbox;
							else
								unset($value);
						}
						elseif ((!empty($field['type']) && $field['type'] == 'text' || $field['type'] == 'color' || $field['type'] == 'textarea' || $field['type'] == 'radio' || ($field['type'] == 'select' && empty($field['multiple']))) && (isset($field['default']) && $value == $field['default']))
							unset($value);
						elseif (!empty($field['type']) && $field['type'] == 'select' && !empty($field['multiple']) && !empty($field['options']) && is_array($field['options'])) {
							// Still need to add support for multi-select fields
						}
						elseif (in_array($field['type'], array('image_upload', 'add_media')) ) {
							$value = !empty($value['url']) ? array_filter(array(
								'url' => esc_url_raw($this->url_relative($value['url'])),
								'width' => !empty($value['width']) ? (int) $value['width'] : false,
								'height' => !empty($value['height']) ? (int) $value['height'] : false)) : array();
						}
					}
					if (!empty($value))
						$save[$id] = $value;
				}
			}
		}
		return !empty($save) ? $save : false;
	}

	/*
	The get_options method takes saved option data and combines it with default option data to determine the current state of options.
	— $fields: an array of options in Thesis Options API Array Format (http://diythemes.com/thesis/rtfm/api/options/array-format/)
	— $values: an array of values retrieved from a database
	*/
	public function get_options($fields, $values) { // Returns options + defaults (defaults are not saved to the db)
		if (!is_array($fields)) return array();
		$values = is_array($values) ? $values : array();
		$options = array();
		foreach ($fields as $id => $field)
			if (is_array($field)) {
				if ($field['type'] == 'group') {
					if (is_array($field['fields']))
						$options = is_array($group = $this->get_options($field['fields'], $values)) ? array_merge($options, $group) : $options;
				}
				elseif ($field['type'] == 'object_set') {
					if (is_array($field['objects']))
						foreach ($field['objects'] as $name => $object)
							if (is_array($object['fields']))
								if ($object_values = $this->get_options($object['fields'], !empty($values[$name]) ? $values[$name] : array()))
									foreach ($object_values as $obj_id => $val)
										$options[$name][$obj_id] = $val;
				}
				elseif ($field['type'] == 'custom') {
					if (is_array($field['options']))
						foreach ($field['options'] as $custom_id => $default)
							if (!empty($values[$custom_id]))
								$options[$custom_id] = $values[$custom_id];
							elseif (!empty($default))
								$options[$custom_id] = $default;
				}
				else {
					if ($field['type'] == 'checkbox' && is_array($field['options']))
						foreach ($field['options'] as $option => $option_value) {
							$options[$id][$option] = isset($values[$id][$option]) ? (bool) $values[$id][$option] : (!empty($field['default'][$option]) ? $field['default'][$option] : false);
							if (empty($options[$id][$option]))
								unset($options[$id][$option]);
						}
					else
						$options[$id] = !empty($values[$id]) ? $values[$id] : (!empty($field['default']) ? $field['default'] : false);
				}
				if (empty($options[$id]))
					unset($options[$id]);
			}
		return $options;
	}

	/*
	Handle uplaoded image data and return a value suitable for saving.
	— $location: the location of the uploaded file in the $_FILES array
	— $type: determines where to store the uploaded image; 'default' and 'box' use the WP upload system, 'skin' uses the active Skin's /images folder.
	— $post_id: provide a post ID if one is associated with the image
	*/
	public function save_image($location, $type = 'default', $post_id = 0) {
		if (empty($_FILES[$location]) || $_FILES[$location]['error'] === 4 || !current_user_can('upload_files'))
			return false;
		$url = $width = $height = $id = false;
		// plain old upload
		if ($type === 'default' || $type === 'box') {
			$post_id = (int) abs($post_id);
			// returns the attachment id
			$id = media_handle_upload($location, $post_id);
			$id = (int) $id;
			$post = get_post($id);
			if (empty($post->guid))
				return false;
			$url = $post->guid;
			if (empty($url)) return false;
			$metadata = wp_get_attachment_metadata($id);
			if (empty($metadata)) {
				$wp_upload = wp_upload_dir(); // path
				$image_data = @getimagesize("{$wp_upload['path']}/". basename($post->guid));
			}
			$height = !empty($metadata['height']) ? $metadata['height'] : (!empty($image_data[1]) ? $image_data[1] : false);
			$width = !empty($metadata['width']) ? $metadata['width'] : (!empty($image_data[0]) ? $image_data[0] : false);
		}
		elseif ($type === 'skin') {
			$upload = $_FILES[$location];
			if (! @is_uploaded_file($upload['tmp_name']) || ! ($upload_data = @getimagesize($upload['tmp_name'])) || $upload['error'] > 0 ||
				! defined('THESIS_USER_SKIN_IMAGES'))
				return false;
			if (! @is_dir(THESIS_USER_SKIN_IMAGES) && get_filesystem_method() === 'direct') {
				include_once(ABSPATH. 'wp-admin/includes/file.php');
				WP_Filesystem();
				if (!$GLOBALS['wp_filesystem']->mkdir(THESIS_USER_SKIN_IMAGES))
					return false;
			}
			$ext = explode('/', $upload_data['mime']);
			$ext = strtolower($ext[1]) == 'jpeg' ? 'jpg' : (strtolower($ext[1]) == 'tiff' ? 'tif' : strtolower($ext[1]));
			if (!stristr($upload['name'], ".$ext")) {
				$a = explode('.', $upload['name']);
				array_pop($a);
				array_push($a, $ext);
				$upload['name'] = implode('.', $a);
			}
			// make a unique file name
			$upload['name'] = wp_unique_filename(THESIS_USER_SKIN_IMAGES, $upload['name']);
			$path = untrailingslashit(THESIS_USER_SKIN_IMAGES). "/{$upload['name']}";
			if (@move_uploaded_file($upload['tmp_name'], $path) === false)
				return false;
			$url = untrailingslashit(THESIS_USER_SKIN_IMAGES_URL). "/{$upload['name']}";
			$height = $upload_data[0];
			$width = $upload_data[1];
		}
		$return = array_filter(array(
			'url' => esc_url_raw($this->url_relative($url)),
			'width' => !empty($width) ? (int) $width : false,
			'height' => !empty($height) ? (int) $height : false,
			'id' => !empty($id) ? $id : false));
		return !empty($return) ? $return : false;
	}

/*---:[ Thesis Options helpers ]:---*/

	/*
	Shortcut method to add standard HTML options to a Box.
	— $tags: to add an HTML tag option, provide an array of potential tags here
	— $default: indicate a default HTML tag value here
	— $attributes: set to true to add an HTML attributes option
	— $group: to make these HTML options a group, set this to true
	*/
	public function html_options($tags = false, $default = false, $attributes = false, $group = false) {
		$options['html'] = !empty($tags) && is_array($tags) ? array_filter(array(
			'type' => 'select',
			'label' => $this->strings['html_tag'],
			'options' => $tags,
			'default' => $default)) : false;
		$options = array_filter(array_merge($options, array(
			'id' => array(
				'type' => 'text',
				'width' => 'medium',
				'code' => true,
				'label' => $this->strings['html_id'],
				'tooltip' => $this->strings['id_tooltip']),
			'class' => array(
				'type' => 'text',
				'width' => 'medium',
				'code' => true,
				'label' => $this->strings['html_class'],
				'tooltip' => $this->strings['class_tooltip']. $this->strings['class_note']),
			'attributes' => !empty($attributes) ? array(
				'type' => 'text',
				'width' => 'medium',
				'label' => sprintf(__('%s Attributes', 'thesis'), $this->base['html']),
				'tooltip' => __($this->strings['attr_name'], 'thesis')) : false)));
		return !empty($group) ? array(
			'html' => array(
				'type' => 'group',
				'label' => sprintf(__('%s Options', 'thesis'), $this->base['html']),
				'fields' => $options)) : $options;
	}

	/*
	Shortcut method for any output that must allow for traditional HTML text formatting.
	Note: This method really belongs in the Thesis WP API.
	— $text: the text to scrub for allowed tags
	— $allowed: an array of allowed tags in wp_kses array format
	*/
	public function allow_html($text, $allowed = false) {
		$allowed = !empty($allowed) && is_array($allowed) ? $allowed : array( // wp_kses allowed tags array format
			'a' => array(
				'id' => array(),
				'class' => array(),
				'href' => array(),
				'rel' => array(),
				'title' => array(),
				'target' => array()),
			'span' => array(
				'class' => array(),
				'title' => array()),
			'em' => array(),
			'strong' => array(),
			'abbr' => array(
				'title' => array()),
			'u' => array(),
			'i' => array(),
			'code' => array(),
			'sup' => array(),
			'sub' => array(),
			'cite' => array(),
			'kbd' => array(),
			'strike' => array(),
			'br' => array());
		return trim(wptexturize(wp_kses($text, $allowed))); 
	}

/*---:[ Miscellaenous helpers ]:---*/

	/*
	Parse URL and return only the relative path. This is useful for "futureproofing" URLs
	against domain changes in the future.
	— $url: the URL to parse
	*/
	public function url_relative($url) {
		if (empty($url)) return;
		return str_replace(get_site_url(), '', $url);
	}

	/*
	Parse URLs and return a relative URL with the current site URL preprended.
	This method is useful for outputting saved URL data in a way that adapts the URL to the current domain.
	— $url: the saved URL to be 'currentized'
	*/
	public function url_current($url) {
		if (empty($url)) return;
		return esc_url(parse_url($url, PHP_URL_SCHEME) == NULL ?
			get_site_url(). $url :
			$url);
	}

	/*
	Operational method to sort a multi-dimensional associative array by a provided index.
	— $array: the multi-dimensional array to sort
	— $index: the array which will determine the sort order
	— $order: sort ordering, 'false' is a reverse sort (largest value comes first)
	*/
	public function sort_by($array = array(), $index = '', $order = false) {
		if (empty($array) || !is_array($array) || empty($index) || !is_string($index)) return false;
		$sort_by = $sorted = array();
		foreach ($array as $i => $item)
			if (isset($item[$index]))
				$sort_by[$i] = $item[$index];
		if (count($sort_by) !== count($array))
			return false;
		if (empty($order))
			arsort($sort_by);
		else
			asort($sort_by);
		foreach ($sort_by as $i => $item)
			$sorted[$i] = $array[$i];
		return $sorted;
	}

	/*
	Operational method to cleanly trim text for the WP excerpt.
	Note: This is dumb; instead of a meta_description parameter, a filterable excerpt length parameter makes way more sense.
	— $text: the text to trim
	— $meta_description: is the text being trimmed for a meta description?
	— $first_paragraph: grab the text from only the first paragraph (assuming $text is composed of HTML)
	*/
	public function trim_excerpt($text = '', $meta_description = false, $first_paragraph = false) {
		global $post;
		if (empty($text) && !in_the_loop())
			return '';
		$text = !empty($text) ? $text : (!empty($post->post_excerpt) ? $post->post_excerpt : $post->post_content);
		$text = preg_replace('/<(h[1-4]{1})>.*<\/\1>/', '', $text);
		$text = strip_shortcodes($text);
		if ($first_paragraph) {
			$text = wp_kses($text, array(
				'a' => array('href' => true, 'title' => true),
				'abbr' => array('title' => true),
				'acronym' => array('title' => true),
				'code' => true,
				'em' => true,
				'strong' => true));
			preg_match('/\<p\>(.*)\<\/p\>/i', wpautop($text), $results);
			$return = '<p>'. (!empty($results[1]) ? $results[1] : $text). '</p>';
		}
		else {
			$text = wp_trim_words(strip_tags($text), apply_filters('excerpt_length', 55), apply_filters('excerpt_more', ' ' . '[...]'));
			$return = apply_filters('thesis_trim_excerpt', (bool) $meta_description && strlen($text) > 155 ? substr($text, 0, 155) : $text);
		}
		return $return;
	}

	/*
	Returns an array containing a Skin's current CSS Variable values.
	This method's existence is dubious, as access to CSS Variable values likely should not need to be accessed outside normally-defined areas.
	*/
	public function get_css_variables() {
		global $thesis;
		if (!is_array($css_variables = $this->get_option("{$thesis->skins->skin['class']}_vars"))) return false;
		$items = array();
		foreach ($css_variables as $var)
			if (!empty($var['ref']) && !empty($var['css']))
				$items[$var['ref']] = $var['css'];
		return $items;
	}

/*---:[ Thesis UI methods ]:---*/

	/*
	Easily output custom alert messages within the Thesis UI.
	*/
	public function alert($message, $id = false, $ajax = false, $status = false, $depth = false) {
		if (empty($message)) return;
		$id = $id ? " id=\"$id\"" : '';
		$ajax = $ajax ? '_ajax' : '';
		$status = $status == 'good' ? ' t_good' : ($status == 'bad' ? ' t_bad' : ($status == 'warning' ? ' t_warning' : ''));
		$tab = str_repeat("\t", (is_numeric($depth) ? $depth : 2));
		return
			"$tab<div$id class=\"t{$ajax}_alert$status\">\n".
			"$tab\t<div class=\"t_message\">\n".
			"$tab\t\t<p>$message</p>\n".
			"$tab\t</div>\n".
			"$tab</div>\n";
	}

	/*
	Easily output a popup box within the Thesis UI.
	*/
	public function popup($args = array()) {
		$id = $title = $body = $type = '';
		$name = $menu = $panes = array();
		$depth = 0;
		extract($args); // array('id' (string), 'title' (string), 'name' (array), 'menu' (array), 'panes' (array), 'body' (string), 'depth' (int))
		$tab = str_repeat("\t", $depth);
		$li = false;
		$type = $type ? " type_$type" : '';
		$title = trim($title);
		$name = !empty($name) && is_array($name) ?
			": <input type=\"text\" data-style=\"input\" id=\"{$name['id']}\" data-id=\"$id\" class=\"t_popup_name\" name=\"{$name['name']}\" value=\"". esc_attr($name['value']). '"'. (is_numeric($name['tabindex']) ? " tabindex=\"{$name['tabindex']}\"" : ''). " />" : (!empty($name) ? ": $name" : '');
		if (is_array($menu))
			foreach ($menu as $pane => $text)
				$li[$pane] = "<li data-pane=\"$pane\">$text</li>";
		if (is_array($panes))
			foreach ($panes as $pane => $options)
				$body .=
					"$tab\t\t\t<div class=\"pane pane_$pane\">\n".
					$options.
					"$tab\t\t\t</div>\n";
		return
			"$tab<div id=\"popup_$id\" data-id=\"$id\" class=\"t_popup\">\n".
			"$tab\t<div class=\"t_popup_html$type\">\n".
			"$tab\t\t<div class=\"t_popup_head\" data-style=\"box\">\n".
			"$tab\t\t\t<span class=\"t_popup_close\" data-style=\"close\" title=\"". __('click to close', 'thesis'). "\">&times;</span>\n".
			"$tab\t\t\t<h4>$title$name</h4>\n".
			(is_array($li) ?
			"$tab\t\t\t<ul class=\"t_popup_menu\">\n".
			"$tab\t\t\t\t". implode("\n$tab\t\t\t\t", $li). "\n".
			"$tab\t\t\t</ul>\n" : '').
			"$tab\t\t</div>\n".
			"$tab\t\t<div class=\"t_popup_body\">\n".
			$body.
			"$tab\t\t</div>\n".
			"$tab\t</div>\n".
			"$tab</div>\n";
	}

	/*
	Easily add a file uploader to the Thesis UI.
	*/
	public function uploader($name, $depth = false) {
		$tab = str_repeat("\t", is_numeric($depth) ? $depth : 0);
		return
			"$tab<iframe style=\"width:100%;height:100%;". (stripos($_SERVER['HTTP_USER_AGENT'], 'mozilla') >= 0 && $name == 'thesis_images' ? "position:absolute;" : ''). "\" frameborder=\"0\" src=\"". admin_url("admin-post.php?action={$name}_window&window_nonce=". wp_create_nonce('thesis_upload_iframe')). "\" id=\"thesis_upload_iframe_$name\">\n".
			"$tab</iframe>\n";
	}

	/*
	Common strings used in the Thesis UI are accessible here.
	Note: When building new software, do not introduce complication and break up references like this.
	— $thesis->api->base: contains common acronyms and shorthand references for annoying HTML
	— $thesis->api->strings: contains longer strings
	*/
	private function strings() {
		$this->base = array(
			'html' => '<abbr title="HyperText Markup Language">HTML</abbr>',
			'css' => '<abbr title="Cascading Style Sheet">CSS</abbr>',
			'url' => '<abbr title="Uniform Resource Locator">URL</abbr>',
			'seo' => '<abbr title="Search Engine Optimization">SEO</abbr>',
			'php' => '<abbr title="Recursive acronym for Hypertext Preprocessor">PHP</abbr>',
			'rss' => '<abbr title="Really Simple Syndication">RSS</abbr>',
			'ssl' => '<abbr title="Secure Socket Layer">SSL</abbr>',
			'wp' => '<abbr title="WordPress">WP</abbr>',
			'api' => '<abbr title="Application Programming Interface">API</abbr>',
			'id' => '<code>id</code>',
			'class' => '<code>class</code>');
	 	return array(
			'page' => 'Page',
			'pages' => 'Pages',
			'search' => 'Search',
			'edit' => 'Edit',
			'name' => 'Name',
			'email' => 'Email',
			'website' => 'Website',
			'required' => 'Required',
			'comment' => 'Comment',
			'submit' => 'Submit',
			'click_to_edit' => 'click to edit',
			'click_to_read' => 'click to read',
			'comment_singular' => 'comment',
			'comment_plural' => 'comments',
			'comment_permalink' => 'permalink to this comment', // End front-end strings
			'save' => 'Save',
			'cancel' => 'Cancel',
			'delete' => 'Delete',
			'create' => 'Create',
			'select' => 'Select',
			'site' => 'Site',
			'skin' => 'Skin',
			'custom' => 'Custom',
			'editor' => 'Editor',
			'package' => 'Package',
			'packages' => 'Packages',
			'variable' => 'Variable',
			'variables' => 'Variables',
			'override' => 'Override',
			'reference' => 'Reference',
			'comments' => 'Comments',
			'title_tag' => 'Title Tag',
			'meta_description' => 'Meta Description',
			'meta_keywords' => 'Meta Keywords',
			'meta_robots' => 'Meta Robots',
			'custom_template' => 'Custom Template',
			'html_head' => sprintf('%s Head', $this->base['html']),
			'title_counter' => 'Search engines allow a maximum of 70 characters for the title.',
			'description_counter' => 'Search engines allow a maximum of roughly 150 characters for the description.',
			'html_tag' => sprintf('%s Tag', $this->base['html']),
			'html_id' => sprintf('%1$s %2$s', $this->base['html'], $this->base['id']),
			'html_class' => sprintf('%1$s %2$s', $this->base['html'], $this->base['class']),
			'id_tooltip' => sprintf('If you need to target this box individually with %1$s or JavaScript, you can enter an %2$s here.<br /><br /><strong>Note:</strong> %2$ss cannot begin with numbers, and only one %2$s is valid per box!', $this->base['css'], $this->base['id']),
			'class_tooltip' => sprintf('If you want to target this box with %1$s or JavaScript, you should enter a %2$s name here.', $this->base['css'], $this->base['class']),
			'class_note' => sprintf('<br /><br /><strong>Note:</strong> %1$s names cannot begin with numbers! Separate multiple %1$ses with spaces.', $this->base['class']),
			'hook_label' => 'Unique Hook Name',
			'hook_tooltip_1' => 'If you want to access this box programmatically, you should supply a unique hook name here. Your hook references will then become:',
			'hook_tooltip_2' => '&hellip;where <code>{name}</code> is equal to the value you enter here.',
			'posts_to_show' => 'Number of Posts to Show',
			'avatar_size' => 'Avatar Size',
			'avatar_tooltip' => sprintf('Your author avatars will display at the size you enter here. If you enter nothing, your avatars will be 96px square, and we recommend doing this so you can instead control the sizing with %s. Please note that avatars will always be returned as square images (eg. 96&times;96 pixels).', $this->base['css']),
			'comment_term_singular' => 'Comment Term Singular',
			'comment_term_plural' => 'Comment Term Plural',
			'character_separator' => 'Character Separator',
			'alt_tooltip' => sprintf('Adding <code>alt</code> text will help you derive the maximum %s benefit from your image. Be concise and descriptive!', $this->base['seo']),
			'caption_tooltip' => 'After headlines, sub-headings and image captions are the most commonly read items on web pages. Don&#8217;t miss this opportunity to engage your readers&#8212;add a caption to your image!',
			'frame_label' => 'Frame This Image?',
			'frame_tooltip' => sprintf('If you set this option to true, then an %s class of <code>frame</code> will be added to your image. Please note that your active Skin may not support image framing.', $this->base['html']),
			'frame_option' => 'add a frame to this image',
			'alignment' => 'Default Alignment',
			'alignment_tooltip' => sprintf('If you select an alignment, a corresponding %1$s %2$s will be added to your image. Please note that your active Skin may not support image alignment.', $this->base['html'], $this->base['class']),
			'alignleft' => 'left with text wrap',
			'alignright' => 'right with text wrap',
			'aligncenter' => 'centered (no wrap)',
			'alignnone' => 'left with no text wrap',
			'skin_default' => 'use Skin default (recommended)',
			'display_options' => 'Display Options',
			'date_tooltip' => sprintf('This field accepts a <a href="%1$s" target="_blank">%2$s date format</a>.', esc_url('http://php.net/manual/en/function.date.php'), $this->base['php']),
			'show_label' => 'show input label',
			'placeholder' => 'Placeholder Text',
			'placeholder_tooltip' => sprintf('By providing %s5 placeholder text, you can give users an example of the info they should enter into this form field.', $this->base['html']),
			'submit_button_text' => 'Submit Button Text',
			'intro_text' => 'Intro Text',
			'link_text' => 'Link Text',
			'use_post_title' => 'use post title (recommended)',
			'use_custom_text' => 'use custom text',
			'custom_link_text' => 'Custom Link Text',
			'no_html' => sprintf('no %s tags allowed', $this->base['html']),
			'include_http' => '(including <code>http://</code> or <code>https://</code>)',
			'this_page' => 'this page',
			'not_recommended' => '(not recommended)',
			'tracking_scripts' => 'Tracking Scripts',
			'saved' => 'saved',
			'not_saved' => 'not saved',
			'auto_wp_label' => 'Automatic WordPress Post Classes',
			'auto_wp_tooltip' => 'WordPress can output post classes that allow you to target specific types of posts more easily. Target by post type, category, tag, taxonomy, author, and more.',
			'auto_wp_option' => 'Use automatically-generated WordPress post classes',
		    'attr_name' => sprintf('You can add attributes to your %s containers; for example, <code>data-type="post"</code>. You can even use this space for multiple attribute declarations.', $this->base['html']));
	}

	/*
	Summon an object that can output and handle a Thesis-style Box Form (like the one in the Template HTML Editor).
	*/
	public function get_box_form() {
		require_once(THESIS_API. '/box_form.php');
		return new thesis_box_form;
	}

/*---:[ Backwards hook compatibility ]:---*/

	private function deprecated_hooks() {
		add_action('hook_head', array($this, 'hook_head'));
		add_action('hook_before_html', array($this, 'hook_before_html'));
		add_action('hook_after_html', array($this, 'hook_after_html'));
	}

	public function hook_head() {
		do_action('thesis_hook_head');
	}

	public function hook_before_html() {
		do_action('thesis_hook_before_html');
	}

	public function hook_after_html() {
		do_action('thesis_hook_after_html');
	}

/*---:[ WP multisite methods ]:---*/

	/*
	Outputs the CSS for sites using WP Multisite.
	*/
	public function ms_css() {
		$css = get_option('thesis_raw_css') ? get_option('thesis_raw_css') : file_get_contents(THESIS_USER_SKIN. '/css.css');
		header('Content-Type: text/css', true, 200);
		printf('%s', strip_tags($css));
		exit;
	}
}