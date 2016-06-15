<?php
/*
Copyright 2015 DIYthemes, LLC. Patent pending. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/
*/
class thesis_admin_home {
	public function __construct() {
		if (!empty($_GET['page']) && $_GET['page'] == 'thesis' && empty($_GET['canvas'])) {
			add_action('thesis_admin_canvas', array($this, 'admin'));
			add_action('thesis_admin_home', array($this, 'site'), 50);
		}
	}

	public function admin() {
		global $thesis;
		$tip = $this->bubble_tips();
		echo
			(!is_dir(WP_CONTENT_DIR. '/thesis') ?
			"<p><a data-style=\"button save\" style=\"margin-bottom: 24px;\" href=\"".
			wp_nonce_url(admin_url('update.php?action=thesis-install-components'), 'thesis-install').
			"\">". __('Click to get started!', 'thesis'). "</a></p>" : ''),
			"\t\t<div class=\"t_canvas_left t_text\"", (!file_exists(WP_CONTENT_DIR. '/thesis') ? " style=\"opacity: 0.15;\"" : ''), ">\n",
			$this->update_nag(3);
		do_action('thesis_admin_home');
		echo
			"\t\t</div>\n",
			"\t\t<div class=\"t_canvas_right\">\n";
		do_action('thesis_current_skin');
		echo
			"\t\t\t<div class=\"t_bubble\">\n",
			"\t\t\t\t<p>{$tip['tip']}</p>\n",
			"\t\t\t</div>\n",
			"\t\t\t<div class=\"t_bubble_cite\">\n",
			"\t\t\t\t<img class=\"t_bubble_pic\" src=\"{$tip['img']}\" alt=\"{$tip['name']}\" width=\"90\" height=\"90\" />\n",
			"\t\t\t\t<p>{$tip['name']}</p>\n",
			"\t\t\t</div>\n",
			"\t\t</div>\n";
	}

	private function update_nag($tab = 0) {
		global $thesis;
		if (!is_array($thesis->admin->updates) || empty($thesis->admin->updates))
			return '';
		$tab = str_repeat("\t", $tab);
		return
			"$tab<div class=\"t_update_alert\">\n".
			"$tab\t<h3 class=\"t_update_available\">". __('Updates Available!', 'thesis'). "</h3>\n".
			"$tab\t<ul>\n".
			(!empty($thesis->admin->updates['core']) ?
			"$tab\t\t<li>". sprintf(__('Thesis %1$s is available. <a href="%2$s">Update Now!</a>', 'thesis'), $thesis->admin->updates['core']['new_version'], esc_url(wp_nonce_url('update.php?action=upgrade-theme&amp;theme=thesis', 'upgrade-theme_thesis'))). "</li>\n" : '').
			(!empty($thesis->admin->updates['skins']) && is_array($thesis->admin->updates['skins']) ?
			"$tab\t\t<li>". sprintf(__('You have <a href="%1$s">%2$s Skin update%3$s available</a>.', 'thesis'), esc_url(admin_url('admin.php?page=thesis&canvas=select_skin')), count($thesis->admin->updates['skins']), count($thesis->admin->updates['skins']) > 1 ? 's' : ''). "</li>\n" : '').
			(!empty($thesis->admin->updates['boxes']) && is_array($thesis->admin->updates['boxes']) ?
			"$tab\t\t<li>". sprintf(__('You have <a href="%1$s">%2$s Box update%3$s available</a>.', 'thesis'), esc_url(admin_url('admin.php?page=thesis&canvas=boxes')), count($thesis->admin->updates['boxes']), count($thesis->admin->updates['boxes']) > 1 ? 's' : ''). "</li>\n" : '').
			"$tab\t</ul>\n".
			"$tab</div>\n";
	}

	public function site() {
		$items = '';
		foreach (apply_filters('thesis_site_menu', array()) as $item)
			if ($item['url'] !== '#')
				$items .= "\t\t\t\t<li><strong><a href=\"{$item['url']}\">{$item['text']}</a></strong>". (!empty($item['description']) ? ": {$item['description']}" : ''). "</li>\n";
		echo
			"\t\t\t<h3>", __('Sitewide Options', 'thesis'), "</h3>\n",
			"\t\t\t<p>", __('The following Sitewide Options provide extended functionality for your site, regardless of the Skin you&#8217;re using.', 'thesis'), "</p>\n",
			"\t\t\t<ul>\n",
			$items,
			"\t\t\t</ul>\n",
			"\t\t\t<p>", __('<strong>Note:</strong> You can also use the Site menu at the top of the screen to access this functionality.', 'thesis'), "</p>\n";
	}

	private function bubble_tips() {
		$authors = array(
			'pearsonified' => array(
				'name' => 'Chris Pearson',
				'img' => 'pearsonified.png'),
			'missieur' => array(
				'name' => 'Missieur',
				'img' => 'missieur.png'));
		$tips = array(
			'category-seo' => array(
				'tip' => __('Supercharge the <abbr title="Search Engine Optimization">SEO</abbr> of your archive pages by supplying Archive Title and Archive Content information on the editing pages for categories, tags, and taxonomies.', 'thesis'),
				'author' => 'pearsonified'),
			'404page' => array(
				'tip' => sprintf(__('Thesis lets you control the content of your 404 page. All you have to do is <a href="%s">specify a 404 page</a>, and boom&#8212;magic!', 'thesis'), admin_url('admin.php?page=thesis&canvas=thesis_404')),
				'author' => 'pearsonified'),
			'blog' => array(
				'tip' => sprintf(__('In addition to making Thesis, DIYthemes publishes a killer blog dedicated to helping you run a better website. <a href="%s">Check out our blog</a>.', 'thesis'), 'http://diythemes.com/thesis/'),
				'author' => 'pearsonified'),
			'updates' => array(
				'tip' => __('Thesis features automatic updates for Skins, Boxes, <em>and</em> the Thesis core. You win.', 'thesis'),
				'author' => 'missieur'),
			'verify' => array(
				'tip' => sprintf(__('You like ranking in search engines, don&#8217;t ya? Then be sure to verify your site with both Google and Bing Webmaster Tools on the <a href="%s">Site Verification page.</a>', 'thesis'), admin_url('admin.php?page=thesis&canvas=thesis_meta_verify')),
				'author' => 'missieur'),
			'march-2008' => array(
				'tip' => __('<strong>Did you know?</strong><br />Thesis launched on March 29, 2008.', 'thesis'),
				'author' => 'pearsonified'),
			'seo-tips' => array(
				'tip' => sprintf(__('Besides using Thesis, what else can you do to improve your <abbr title="Search Engine Optimization">SEO</abbr>? Check out DIYthemes&#8217; series on <a href="%s">WordPress <abbr title="Search Engine Optimization">SEO</abbr> for Everybody</a>.', 'thesis'), 'http://diythemes.com/thesis/wordpress-seo/'),
				'author' => 'pearsonified'),
			'analytics'	=> array(
				'tip' => sprintf(__('Amp up your site&#8217;s search engine performance by providing <a href="%1$s">Blog Page <abbr title="Search Engine Optimization">SEO</abbr></a> details.', 'thesis'), admin_url('admin.php?page=thesis&canvas=thesis_home_seo')),
				'author' => 'missieur'),
			'email-marketing' => array(
				'tip' => sprintf(__('<strong>Did you know?</strong><br />Email marketing is probably the best way to leverage the web to grow your business. Get started today with DIYthemes&#8217; exclusive guide: <a href="%1$s">Email Marketing for Everybody</a>.', 'thesis'), 'http://diythemes.com/thesis/email-marketing-everybody/'),
				'author' => 'missieur'),
			'custom-templates' => array(
				'tip' => sprintf(__('No matter which Skin you use, you can always create custom templates in the <a href="%s">Skin Editor</a> for things like landing pages, checkout pages, and more.', 'thesis'), set_url_scheme(home_url('?thesis_editor=1'))),
				'author' => 'pearsonified'),
			'custom-css' => array(
				'tip' => sprintf(__('<strong>How do I add custom <abbr title="Cascading Style Sheet">CSS</abbr>?</strong><br />With Thesis, you don&#8217;t need a separate file for your customizations&#8212;you can simply add your custom <abbr title="Cascading Style Sheet">CSS</abbr> on the <a href="%s">Custom <abbr title="Cascading Style Sheet">CSS</abbr> page</a>.', 'thesis'), admin_url('admin.php?page=thesis&canvas=custom_css')),
				'author' => 'pearsonified'));
		$pick = $tips;
		shuffle($pick);
		$tip = array_shift($pick);
		$tip['name'] = $authors[$tip['author']]['name'];
		$tip['img'] = THESIS_IMAGES_URL. "/{$authors[$tip['author']]['img']}";
		return $tip;
	}
}