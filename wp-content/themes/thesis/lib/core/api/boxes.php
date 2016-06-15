<?php
/*
Copyright 2012 DIYthemes, LLC. Patent pending. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/
*/
class thesis_tracking_scripts extends thesis_box {
	public $type = false;
	protected $filters = array('menu' => 'site');

	protected function translate() {
		global $thesis;
		$this->title = __($thesis->api->strings['tracking_scripts'], 'thesis');
		$this->filters['description'] = __('Add tracking scripts to the footer of your site', 'thesis');
	}

	protected function construct() {
		global $thesis;
		if (is_admin() && ($update = $thesis->api->get_option('thesis_scripts')) && !empty($update)) {
			update_option($this->_class, ($this->options = array('scripts' => $update)));
			delete_option('thesis_scripts');
			wp_cache_flush();
		}
		elseif (!empty($this->options['scripts']))
			add_action('hook_after_html', array($this, 'html'), 9);
	}

	protected function class_options() {
		global $thesis;
		return array(
			'scripts' => array(
				'type' => 'textarea',
				'rows' => 10,
				'code' => true,
				'label' => $this->title,
				'description' => __('please include <code>&lt;script&gt;</code> tags', 'thesis'),
				'tooltip' => sprintf(__('Any scripts you add here will be displayed just before the closing <code>&lt;/body&gt;</code> tag on every page of your site.<br /><br />If you need to add a script to your %1$s <code>&lt;head&gt;</code>, visit the <a href="%2$s">%1$s Head Editor</a> and click on the <strong>Head Scripts</strong> box.', 'thesis'), $thesis->api->base['html'], admin_url('admin.php?page=thesis&canvas=head'))));
	}

	public function html() {
		if (empty($this->options['scripts'])) return;
		echo trim(stripslashes($this->options['scripts'])), "\n";
	}
}

class thesis_meta_verify extends thesis_box {
	public $type = false;
	protected $filters = array(
		'menu' => 'site',
		'canvas_left' => true);
	private $allowed = array(
		'meta' => array(
			'name' => array(),
			'content' => array()));

	protected function translate() {
		$this->title = __('Site Verification', 'thesis');
		$this->filters['description'] = __('Verify your site with Google and/or Bing', 'thesis');
	}

	protected function construct() {
		if (empty($this->options)) return;
		add_action('hook_head', array($this, 'html'), 1);
	}

	protected function class_options() {
		$tooltip = __('For optimal search engine performance, we recommend verifying your site with', 'thesis');
		return array(
			'google' => array(
				'type' => 'text',
				'width' => 'full',
				'label' => __('Google Site Verification', 'thesis'),
				'tooltip' => sprintf(__('%1$s <a href="%2$s" target="_blank">Google Webmaster Tools</a>. Copy and paste the entire Google verification <code>&lt;meta&gt;</code> tag or just the unique <code>content=&quot;&quot;</code> value into this field.', 'thesis'), $tooltip, 'https://www.google.com/webmasters/tools/')),
			'bing' => array(
				'type' => 'text',
				'width' => 'full',
				'label' => __('Bing Site Verification', 'thesis'),
				'tooltip' => sprintf(__('%1$s <a href="%2$s" target="_blank">Bing Webmaster Tools</a>. Copy and paste the entire Bing verification <code>&lt;meta&gt;</code> tag or just the unique <code>content=&quot;&quot;</code> value into this field.', 'thesis'), $tooltip, 'http://www.bing.com/toolbox/webmasters/')));
	}

	public function html() {
		global $thesis;
		if (!is_front_page()) return;
		echo
			(!empty($this->options['google']) ? (preg_match('/<meta/', $this->options['google']) ?
			trim(wp_kses(stripslashes($this->options['google']), $this->allowed)). "\n" :
			"<meta name=\"google-site-verification\" content=\"". trim($thesis->api->esc($this->options['google'])). "\" />\n") : ''),
			(!empty($this->options['bing']) ? (preg_match('/<meta/', $this->options['bing']) ?
			trim(wp_kses(stripslashes($this->options['bing']), $this->allowed)). "\n" :
			"<meta name=\"msvalidate.01\" content=\"". trim($thesis->api->esc($this->options['bing'])). "\" />\n") : '');
	}
}

class thesis_google_analytics extends thesis_box {
	public $type = false;
	protected $filters = array('menu' => 'site');

	protected function translate() {
		$this->title = __('Google Analytics', 'thesis');
		$this->filters['description'] = __('Add Google Analytics to your site', 'thesis');
	}

	protected function construct() {
		global $thesis;
		if (is_admin() && ($update = $thesis->api->get_option('thesis_analytics')) && !empty($update)) {
			update_option($this->_class, ($this->options = array('ga' => $update)));
			delete_option('thesis_analytics');
			wp_cache_flush();
		}
		elseif (!empty($this->options['ga']))
			add_action('hook_before_html', array($this, 'html'), 1);
	}

	protected function class_options() {
		return array(
			'ga' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __('Google Analytics Tracking ID', 'thesis'),
				'tooltip' => sprintf(__('To add Google Analytics tracking to Thesis, simply enter your Tracking ID here. This number takes the general form <code>UA-XXXXXXX-Y</code> and can be found by clicking the Home link in your <a href="%s">Google Analytics dashboard</a> (login required).', 'thesis'), 'http://google.com/analytics/')),
			'enable' => array(
				'type' => 'checkbox',
				'options' => array(
					'display' => __('Enable Display Features', 'thesis'))));
	}

	public function html() {
		global $thesis;
		if (empty($this->options['ga']) || is_user_logged_in()) return;
		echo
			"<script>\n",
			"(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){\n",
			"(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),\n",
			"m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)\n",
			"})(window,document,'script','//www.google-analytics.com/analytics.js','ga');\n",
			"ga('create', '", trim($thesis->api->esc($this->options['ga'])), "', 'auto');\n",
			(isset($this->options['enable']['display']) ? "ga('require', 'displayfeatures');\n" : ''),
			"ga('send', 'pageview');\n",
			"</script>\n";
	}
}

class thesis_google_publisher extends thesis_box {
	public $type = false;
	protected $filters = array(
		'menu' => 'site',
		'canvas_left' => true);

	public function translate() {
		$this->title = __('Google Publisher', 'thesis');
		$this->filters['description'] = __('Add Google Publisher to your site (recommended for businesses)', 'thesis');
	}

	public function construct() {
		add_action('hook_head', array($this, 'html'));
	}

	protected function class_options() {
		return array(
			'link' => array(
				'type' => 'text',
				'width' => 'full',
				'title' => __('G&#43; Business Page Link'),
				'label' => __('Google Rel Publisher', 'thesis'),
				'tooltip' => sprintf(__('Please provide the link to your organization&#8217;s G&#43; page. The rel=&#8220;publisher&#8221; spec allows &#8220;businesses, products, brands, entertainment and organizations&#8221; to have &#8220;an identity and presence on Google&#43;&#8221;. For more information, <a href="%1$s" target="_blank">click here</a>.', 'thesis'), 'https://support.google.com/business/answer/4569085?hl=en')));
	}

	public function html() {
		global $thesis;
		if (!is_single() || empty($this->options['link']) || $thesis->wpseo)
			return;
		echo "\n<link rel=\"publisher\" href=\"", esc_url($this->options['link']), "\" />";
	}
}

class thesis_home_seo extends thesis_box {
	public $type = false;
	public $filters = array(
		'menu' => 'site',
		'priority' => 30,
		'canvas_left' => true);

	public function translate() {
		global $thesis;
		$this->title = sprintf(__('Blog Page %s', 'thesis'), $thesis->api->base['seo']);
		$this->filters['description'] = __('Enhance the SEO of your main blog page', 'thesis');
	}

	protected function class_options() {
		global $thesis;
		return array(
			'title' => array(
				'type' => 'text',
				'width' => 'full',
				'label' => __($thesis->api->strings['title_tag'], 'thesis'),
				'counter' => __($thesis->api->strings['title_counter'], 'thesis')),
			'description' => array(
				'type' => 'textarea',
				'rows' => 2,
				'label' => __($thesis->api->strings['meta_description'], 'thesis'),
				'counter' => __($thesis->api->strings['description_counter'], 'thesis')),
			'keywords' => array(
				'type' => 'text',
				'width' => 'full',
				'label' => __($thesis->api->strings['meta_keywords'], 'thesis'),
				'tooltip' => sprintf(__('Please note that keywords will not appear unless you also include the Meta Keywords Box in your <a href="%s">HTML Head template</a>.', 'thesis'), admin_url('admin.php?page=thesis&canvas=head'))));
	}
}

class thesis_404 extends thesis_box {
	public $type = false;
	protected $filters = array(
		'menu' => 'site',
		'priority' => 40);
	private $page = false;

	public function translate() {
		$this->title = __('404 Page', 'thesis');
		$this->filters['description'] = __('Select a 404 page', 'thesis');
	}

	protected function construct() {
		global $thesis;
		$this->page = is_numeric($page = $thesis->api->get_option('thesis_404')) ? $page : $this->page;
		if (!empty($this->page)) {
			add_filter('thesis_404', array($this, 'query'));
			add_filter('thesis_404_page', array($this, 'set_page'));
		}
		if ($thesis->environment == 'admin')
			add_action('admin_post_thesis_404', array($this, 'save'));
	}

	public function query($query) {
		return $this->page ? new WP_Query("page_id=$this->page") : $query;
	}

	public function set_page() {
		return $this->page;
	}

	public function admin_init() {
		add_action('admin_head', array($this, 'css_js'));
	}

	public function css_js() {
		echo
			"<script>\n",
			"var thesis_404;\n",
			"(function($) {\n",
			"thesis_404 = {\n",
			"\tinit: function() {\n",
			"\t\t$('#edit_404').on('click', function() {\n",
			"\t\t\tvar page = $('#thesis_404').val();\n",
			"\t\t\tif (page != 0)\n",
			"\t\t\t\t$(this).attr('href', $('#edit_404').attr('data-base') + page + '&action=edit');\n",
			"\t\t\telse\n",
			"\t\t\t\treturn false;\n",
			"\t\t});\n",
			"\t}\n",
			"};\n",
			"$(document).ready(function($){ thesis_404.init(); });\n",
			"})(jQuery);\n",
			"</script>\n";
	}

	public function admin() {
		global $thesis;
		$tab = str_repeat("\t", $depth = 2);
		echo
			(!empty($_GET['saved']) ? $thesis->api->alert($_GET['saved'] === 'yes' ?
			__('404 page saved!', 'thesis') :
			__('404 not saved. Please try again.', 'thesis'), 'options_saved', true, false, $depth) : ''),
			"$tab<h3>", wptexturize($this->title), "</h3>\n",
			"$tab<form class=\"thesis_options_form\" method=\"post\" action=\"", admin_url('admin-post.php?action=thesis_404'), "\">\n",
			"$tab\t<div class=\"option_item option_field\">\n",
			wp_dropdown_pages(array('name' => 'thesis_404', 'echo' => 0, 'show_option_none' => __('Select a 404 page', 'thesis'). ':', 'option_none_value' => '0', 'selected' => $this->page)),
			"$tab\t</div>\n",
			"$tab\t", wp_nonce_field('thesis-save-404', '_wpnonce-thesis-save-404', true, false), "\n",
			"$tab\t<input type=\"submit\" data-style=\"button save\" class=\"t_save\" id=\"save_options\" value=\"", esc_attr(wptexturize(strip_tags(sprintf(__('%1$s %2$s', 'thesis'), __($thesis->api->strings['save'], 'thesis'), $this->title)))), "\" />\n",
			"$tab</form>\n",
			"$tab<a id=\"edit_404\" data-style=\"button action\" href=\"", admin_url("post.php?post=$this->page&action=edit"), "\" data-base=\"", admin_url('post.php?post='), "\">", wptexturize(sprintf(__('%1$s %2$s', 'thesis'), __($thesis->api->strings['edit'], 'thesis'), $this->title)), "</a>\n";
	}

	public function save() {
		global $thesis;
		$thesis->wp->check('edit_theme_options');
		$thesis->wp->nonce($_POST['_wpnonce-thesis-save-404'], 'thesis-save-404');
		$saved = 'no';
		if (is_numeric($page = $_POST['thesis_404'])) {
			if ($page == '0')
				delete_option('thesis_404');
			else
				update_option('thesis_404', $page);
			$saved = 'yes';
		}
		wp_redirect("admin.php?page=thesis&canvas=$this->_class&saved=$saved");
		exit;
	}
}

class thesis_twitter_profile extends thesis_box {
	protected function translate() {
		global $thesis;
		$this->title = __('Twitter Profile Link', 'thesis');
	}

	protected function construct() {
		add_filter('user_contactmethods', array($this, 'add_twitter'));
	}

	protected function html_options() {
		return array(
			'display' => array(
				'type' => 'radio',
				'label' => __('Display name as:', 'thesis'),
				'tooltip' => sprintf(__('Choose how the author&#8217;s Twitter profile link will be presented. You can edit each author&#8217;s %1$s on their <a href="%2$s">user profile page</a>.', 'thesis'), $this->title, admin_url('users.php')),
				'options' => array(
					'handle' => __('Twitter handle (@YourUsername)', 'thesis'),
					'text' => __('Call-to-action text (&#8220;Follow me on Twitter here.&#8221;)', 'thesis')),
				'default' => 'handle'));
	}

	public function html($args = array()) {
		global $thesis, $post;
		extract($args = is_array($args) ? $args : array());
		$tab = str_repeat("\t", !empty($depth) ? $depth : 0);
		$twitter = str_replace('@', '', trim(get_user_option('twitter', $post->post_author)));
		if (!empty($twitter))
			echo
				"$tab<span class=\"twitter_profile\">", (!empty($this->options['display']) ?
				sprintf(apply_filters($this->_class, __('Follow me on Twitter <a href="%s">here</a>.', 'thesis')), 'https://twitter.com/'. $thesis->api->esc($twitter)) :
				'<a href="https://twitter.com/'. $thesis->api->esc($twitter). "\">@$twitter</a>"),
				"</span>\n";
	}

	public function add_twitter($contacts) {
		$contacts['twitter'] = $this->title;
		return $contacts;
	}
}