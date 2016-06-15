<?php
/*
Copyright 2012 DIYthemes, LLC. Patent pending. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/
*/
class thesis_feed_link extends thesis_box {
	public $type = false;
	protected $filters = array(
		'menu' => 'site',
		'priority' => 25);

	protected function translate() {
		global $thesis;
		$this->title = sprintf(__('%s Feed', 'thesis'), $thesis->api->base['rss']);
		$this->filters['description'] = __('Manage your site&#8217;s RSS feed', 'thesis');
	}

	protected function construct() {
		add_action('hook_head', array($this, 'html'), 1);
	}

	protected function class_options() {
		global $thesis;
		return array(
			'url' => array(
				'type' => 'text',
				'width' => 'long',
				'code' => true,
				'label' => sprintf(__('%1$s %2$s', 'thesis'), $this->title, $thesis->api->base['url']),
				'tooltip' => sprintf(__('If you don&#8217;t enter anything in this field, Thesis will use your default WordPress feed, <code>%1$s</code>. If you&#8217;d like to use any other feed, please enter the feed %2$s here.', 'thesis'), esc_url(get_bloginfo(get_default_feed() . '_url')), $thesis->api->base['url'])));
	}

	public function html() {
		global $thesis;
		if (($url = apply_filters($this->_class, !empty($this->options['url']) ? $this->options['url'] : get_bloginfo(get_default_feed() . '_url'))) && is_string($url) && !empty($url))
			echo '<link rel="alternate" type="application/rss+xml" title="', trim((!empty($thesis->api->options['blogname']) ?
				wptexturize(htmlspecialchars_decode(stripslashes($thesis->api->options['blogname']), ENT_QUOTES)) : __('site', 'thesis')). ' '. __('feed', 'thesis')), '" href="', esc_attr(esc_url($url)), "\" />\n";
	}
}

class thesis_pingback_link extends thesis_box {
	public $type = false;

	protected function translate() {
		global $thesis;
		$this->title = sprintf(__('Pingback %s', 'thesis'), $thesis->api->base['url']);
	}

	protected function construct() {
		if (apply_filters($this->_class, true))
			add_action('hook_head', array($this, 'html'), 1);
	}

	public function html() {
		echo '<link rel="pingback" href="', esc_url(get_bloginfo('pingback_url')), "\" />\n"; #wp
	}
}

class thesis_rel_next_prev extends thesis_box {
	public $type = false;

	public function translate() {
		$this->title = __('Rel Next/Prev Link', 'thesis');
	}

	public function construct() {
		add_action('hook_head', array($this, 'html'));
	}

	public function html() {
		global $thesis, $wp_query, $wp_rewrite;
		if (!is_single() || $thesis->wpseo || empty($wp_query->post->post_content))
			return;
		$content = strpos($wp_query->post->post_content, '<!--nextpage-->') === 0 ? substr($wp_query->post->post_content, 15) : $wp_query->post->post_content;
		if (($count_pages = substr_count($content, '<!--nextpage-->')) === 0)
			return;
		// take count_pages up by one to reflect the number of pages and not just the number of occurences of the string
		$count_pages++;
		$next = $prev = '';
		$current_page = get_query_var('page');
		if ($current_page <= 1)
			// we are on the first page so show rel next for page 2
			$next = "<link rel=\"next\" href=\"". $this->build_url(2). "\" />\n";
		else {
			// the page is greater than 1
			$prev = "<link rel=\"prev\" href=\"". $this->build_url($current_page - 1). "\" />\n";
			if (($current_page + 1) <= $count_pages)
				$next = "<link rel=\"next\" href=\"". $this->build_url($current_page + 1). "\" />\n";
		}
		echo $prev, $next;
	}

	public function build_url($page) {
		global $wp_rewrite, $post;
		return esc_url($page > 1 ? (!$wp_rewrite->using_permalinks() ?
			add_query_arg(array('page' => absint($page)), get_permalink($post->ID)) :
			user_trailingslashit(trailingslashit(get_permalink($post->ID)). absint($page))) :
			get_permalink($post->ID));
	}
}

class thesis_wp_nav_menu extends thesis_box {
	protected function translate() {
		global $thesis;
		$this->name = __('Nav Menu', 'thesis');
		$this->title = sprintf(__('%1$s (%2$s)', 'thesis'), $this->name, $thesis->api->base['wp']);
		$this->control = '≡ '. __('Menu', 'thesis');
	}

	protected function html_options() {
		global $thesis;
		return array(
			'menu_id' => array(
				'type' => 'text',
				'width' => 'medium',
				'code' => true,
				'label' => __($thesis->api->strings['html_id'], 'thesis'),
				'tooltip' => __($thesis->api->strings['id_tooltip'], 'thesis')),
			'menu_class' => array(
				'type' => 'text',
				'width' => 'medium',
				'code' => true,
				'label' => __($thesis->api->strings['html_class'], 'thesis'),
				'tooltip' => sprintf(__('By default, this menu will render with a %1$s of <code>menu</code>, but if you&#8217;d prefer to use a different %1$s, you can supply one here.%2$s', 'thesis'), $thesis->api->base['class'], __($thesis->api->strings['class_note'], 'thesis')),
				'placeholder' => 'menu'),
			'control' => array(
				'type' => 'checkbox',
				'label' => __('Responsive Menu Control', 'thesis'),
				'options' => array(
					'yes' => __('Output menu control button for responsive layouts', 'thesis')),
				'dependents' => array('yes')),
			'control_text' => array(
				'type' => 'text',
				'width' => 'short',
				'label' => __('Menu Control Text', 'thesis'),
				'default' => $this->control,
				'parent' => array(
					'control' => 'yes')));
	}

	protected function options() {
		$menus[''] = __('Select a WP menu:', 'thesis');
		foreach (wp_get_nav_menus() as $menu)
			$menus[(int) $menu->term_id] = esc_attr($menu->name);
		return array(
			'menu' => array(
				'type' => 'select',
				'label' => __('Menu To Display', 'thesis'),
				'tooltip' => sprintf(__('Select a WordPress nav menu for this box to display. To edit your menus, visit the <a href="%s">WordPress nav menu editor</a>.', 'thesis'), admin_url('nav-menus.php')),
				'options' => $menus));
	}

	public function preload() {
		global $thesis_wp_nav_menu_js;
		if (!empty($this->options['control']['yes']) && !$thesis_wp_nav_menu_js) {
			add_filter('thesis_footer_scripts', array($this, 'js'));
			$thesis_wp_nav_menu_js = true;
		}
		add_filter('thesis_footer_scripts', array($this, 'scope'));
	}

	public function html($args = array()) {
		extract($args = is_array($args) ? $args : array());
		add_filter('wp_page_menu', array($this, 'filter_menu'), 10, 2);
		$menu = wp_nav_menu(array_merge($this->options, array('container' => false, 'echo' => false, 'thesis' => true)));
		remove_filter('wp_page_menu', array($this, 'filter_menu'), 10, 2);
		echo str_repeat("\t", !empty($depth) ? $depth : 0),
			(!empty($this->options['control']['yes']) ?
			"<span class=\"menu_control\">". (!empty($this->options['control_text']) ?
				esc_html($this->options['control_text']) : $this->control). "</span>\n" : ''),
			apply_filters($this->_class, $menu), "\n";
	}

	public function filter_menu($menu, $args) {
		if (empty($args['thesis']))
			return $menu;
		preg_match('/<ul>(.*)<\/ul>/', $menu, $li);
		return "<ul class=\"". esc_attr($args['menu_class']). "\">{$li[1]}</ul>";
	}

	public function js($scripts) {
		$scripts[] =
			"<script type=\"text/javascript\">".
			// add getElementsByClassName support to stupid IE
			"(function(d,g){d[g]||(d[g]=function(g){return this.querySelectorAll(\".\"+g)},Element.prototype[g]=d[g])})(document,\"getElementsByClassName\");".
			"(function(){".
			"var classes = document.getElementsByClassName('menu_control');".
			"for (i = 0; i < classes.length; i++) {".
			"classes[i].onclick = function() {".
			"var menu = this.nextElementSibling;".
			"if (/show_menu/.test(menu.className))".
			"menu.className = menu.className.replace('show_menu', '').trim();".
			"else ".
			"menu.className += ' show_menu';".
			"if (/menu_control_triggered/.test(this.className))".
			"this.className = this.className.replace('menu_control_triggered', '').trim();".
			"else ".
			"this.className += ' menu_control_triggered';".
			"};".
			"}".
			"})();".
			"</script>";
		return $scripts;
	}

	public function scope($scripts) {
		if (!empty($this->options['control']['yes'])) {
			if (!empty($this->options['menu_class'])) {
				$class = explode(' ', $this->options['menu_class']);
				$class = array_pop($class);
			}
			else
				$class = 'menu';
			$scripts[$this->_id] = "<noscript><style type=\"text/css\" scoped>.". esc_attr($class). " { display: block; }</style></noscript>";
		}
		return $scripts;
	}
}

class thesis_wp_loop extends thesis_box {
	public $type = 'rotator';
	public $switch = true;

	protected function translate() {
		global $thesis;
		$this->title = sprintf(__('%s Loop', 'thesis'), $thesis->api->base['wp']);
	}

	protected function construct() {
		add_filter('thesis_query', array($this, 'query'));
	}

	protected function term_options() {
		global $thesis;
		return array(
			'posts_per_page' => array(
				'type' => 'text',
				'width' => 'tiny',
				'label' => __($thesis->api->strings['posts_to_show'], 'thesis'),
				'default' => get_option('posts_per_page')));
	}

	protected function template_options() {
		global $thesis;
		return array(
			'title' => $this->title,
			'exclude' => array('single', 'page'),
			'fields' => array(
				'posts_per_page' => array(
					'type' => 'text',
					'width' => 'tiny',
					'label' => __($thesis->api->strings['posts_to_show'], 'thesis'),
					'default' => get_option('posts_per_page'))));
	}

	public function query($query) {
		$posts_per_page = !empty($this->term_options['posts_per_page']) && is_numeric($this->term_options['posts_per_page']) ?
			$this->term_options['posts_per_page'] : (!empty($this->template_options['posts_per_page']) && is_numeric($this->template_options['posts_per_page']) ?
			$this->template_options['posts_per_page'] : false);
		if ($posts_per_page)
			$query->query_vars['posts_per_page'] = $posts_per_page;
		return $query;
	}

	public function html($args = array()) {
		global $thesis, $wp_query, $post;
		extract($args = is_array($args) ? $args : array());
		$post_count = 1;
		if ($wp_query->is_404)
			$wp_query = apply_filters('thesis_404', $wp_query);
		if (apply_filters('thesis_use_custom_loop', false))
			do_action('thesis_custom_loop', $args);
		else {
			if (have_posts())
				while (have_posts()) {
					the_post();
					if (!$wp_query->is_singular)
						do_action('thesis_init_post_meta', $post->ID);
					$this->rotator(array_merge($args, array('post_count' => $post_count)));
					$post_count++;
				}
			elseif (!$wp_query->is_404)
				do_action('thesis_empty_loop');
		}
	}
}

class thesis_wp_featured_image extends thesis_box {
	protected function translate() {
		global $thesis;
		$this->title = sprintf(__('%s Featured Image', 'thesis'), $thesis->api->base['wp']);
	}

	protected function construct() {
		global $thesis;
		if (!$this->_display()) return;
		add_theme_support('post-thumbnails');
		if (empty($thesis->_wp_featured_image_rss)) {
			add_filter('the_content', array($this, 'add_image_to_feed'));
			$thesis->_wp_featured_image_rss = true;
		}
	}

	protected function html_options() {
		global $thesis, $_wp_additional_image_sizes;
		$options = array(
			'full' => __('Full size (default)', 'thesis'),
			'thumbnail' => __('Thumbnail', 'thesis'),
			'medium' => __('Medium', 'thesis'),
			'large' => __('Large', 'thesis'));
		if (!empty($_wp_additional_image_sizes))
			foreach ($_wp_additional_image_sizes as $size => $data)
				$options[$size] = $size;
		return array(
			'size' => array(
				'type' => 'select',
				'label' => __('Featured Image Size', 'thesis'),
				'tooltip' => sprintf(__('Choose the size of the Feature Image for this location. The list includes <a href="%s">WordPress standard image sizes</a> and any other registered image sizes.', 'thesis'), admin_url('options-media.php')),
				'options' => $options,
				'default' => 'full'),
			'alignment' => array(
				'type' => 'select',
				'label' => __($thesis->api->strings['alignment'], 'thesis'),
				'tooltip' => __($thesis->api->strings['alignment_tooltip'], 'thesis'),
				'options' => array(
					'' => __($thesis->api->strings['alignnone'], 'thesis'),
					'left' => __($thesis->api->strings['alignleft'], 'thesis'),
					'right' => __($thesis->api->strings['alignright'], 'thesis'),
					'center' => __($thesis->api->strings['aligncenter'], 'thesis'))),
			'link' => array(
				'type' => 'checkbox',
				'options' => array(
					'link' => __('Link image to post', 'thesis')),
				'default' => array(
					'link' => true)));
	}

	public function html($args = array()) {
		global $post;
		extract($args = is_array($args) ? $args : array());
		$size = !empty($this->options['size']) ? $this->options['size'] : 'full';
		$alignment = !empty($this->options['alignment']) ? ($this->options['alignment'] == 'left' ?
			'alignleft' : ($this->options['alignment'] == 'right' ?
			'alignright' :
			'aligncenter')) : false;
		$image = get_the_post_thumbnail($post->ID, $size, array_filter(array(
			'itemprop' => !empty($schema) ? 'image' : false,
			'class' => !empty($alignment) ? $alignment : false)));
		if (empty($image)) return;
		$html = str_repeat("\t", !empty($depth) ? $depth : 0).
			(!isset($this->options['link']) ?
			'<a class="featured_image_link" href="'. get_permalink(). '">' : '').
			$image.
			(!isset($this->options['link']) ?
			'</a>' : '');
		if (!empty($return))
			return "$html\n";
		else
			echo "$html\n";
	}

	public function add_image_to_feed($content) {
		if (!is_feed()) return $content;
		return $this->html(array('return' => true)). $content;
	}
}

class thesis_comments_intro extends thesis_box {
	public $templates = array('single', 'page');

	protected function translate() {
		$this->title = __('Comments Intro', 'thesis');
	}

	protected function html_options() {
		global $thesis;
		return array(
			'singular' => array(
				'type' => 'text',
				'label' => __($thesis->api->strings['comment_term_singular'], 'thesis'),
				'placeholder' => __($thesis->api->strings['comment_singular'], 'thesis')),
			'plural' => array(
				'type' => 'text',
				'label' => __($thesis->api->strings['comment_term_plural'], 'thesis'),
				'placeholder' => __($thesis->api->strings['comment_plural'], 'thesis')));
	}

	public function html($args = array()) {
		global $thesis, $post;
		extract($args = is_array($args) ? $args : array());
		$tab = str_repeat("\t", !empty($depth) ? $depth : 0);
		$comments = wp_count_comments($post->ID);
		$number = is_object($comments) && !empty($comments->approved) ? $comments->approved : 0;
		if (comments_open())
			echo
				"$tab<div class=\"comments_intro\">",
				apply_filters($this->_class,
				"<span class=\"num_comments\">$number</span> ".
				($number == 1 ? (!empty($this->options['singular']) ?
				$thesis->api->esch($this->options['singular']) : __($thesis->api->strings['comment_singular'], 'thesis')) : (!empty($this->options['plural']) ?
				$thesis->api->esch($this->options['plural']) : __($thesis->api->strings['comment_plural'], 'thesis'))).
				"&#8230; <a href=\"#commentform\" rel=\"nofollow\">". trim(apply_filters("{$this->_class}_add", __('add one', 'thesis'))). "</a>"),
				"</div>\n";
		else
			echo "$tab<p class=\"comments_closed\">",
				trim(esc_html(apply_filters("{$this->_class}_closed", __('Comments on this entry are closed.', 'thesis')))), "</p>\n";
	}
}

class thesis_comments_nav extends thesis_box {
	public $templates = array('single', 'page');

	protected function translate() {
		$this->title = $this->name = __('Comment Navigation', 'thesis');
		$this->previous = apply_filters("{$this->_class}_previous", __('Previous Comments', 'thesis'));
		$this->next = apply_filters("{$this->_class}_next", __('Next Comments', 'thesis'));
	}

	protected function html_options() {
		global $thesis;
		$html = $thesis->api->html_options();
		$html['class']['tooltip'] = sprintf(__('This box already contains a %1$s of <code>comment_nav</code>. If you&#8217;d like to supply another %1$s, you can do that here.%2$s', 'thesis'), $thesis->api->base['class'], __($thesis->api->strings['class_note'], 'thesis'));
		unset($html['id']);
		return array_merge($html, array(
			'previous' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __('Previous Comments Link Text', 'thesis'),
				'placeholder' => $this->previous),
			'next' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __('Next Comments Link Text', 'thesis'),
				'placeholder' => $this->next)));
	}

	public function html($args = array()) {
		global $thesis;
		if (!get_option('page_comments')) return;
		extract($args = is_array($args) ? $args : array());
		$tab = str_repeat("\t", !empty($depth) ? $depth : 0);
		$previous_link = get_previous_comments_link(trim($thesis->api->escht(!empty($this->options['previous']) ?
			stripslashes($this->options['previous']) :
			$this->previous)));
		$next_link = get_next_comments_link(trim($thesis->api->escht(!empty($this->options['next']) ?
			stripslashes($this->options['next']) :
			$this->next)));
		if (empty($previous_link) && empty($next_link)) return;
		echo
			"$tab<div class=\"comment_nav", (!empty($this->options['class']) ? ' '. trim($thesis->api->esc($this->options['class'])) : ''), "\">\n",
			(!empty($next_link) ?
			"$tab\t<span class=\"next_comments\">$next_link</span>\n" : ''),
			(!empty($previous_link) ?
			"$tab\t<span class=\"previous_comments\">$previous_link</span>\n" : ''),
			"$tab</div>\n";
	}
}

class thesis_comments extends thesis_box {
	public $type = 'rotator';
	public $dependents = array(
		'thesis_comment_author',
		'thesis_comment_avatar',
		'thesis_comment_date',
		'thesis_comment_number',
		'thesis_comment_permalink',
		'thesis_comment_edit',
		'thesis_comment_text',
		'thesis_comment_reply');
	public $children = array(
		'thesis_comment_author',
		'thesis_comment_date',
		'thesis_comment_edit',
		'thesis_comment_text',
		'thesis_comment_reply');
	public $abort = false;
	public $templates = array('single', 'page');

	protected function translate() {
		$this->title = $this->name = __('Comment List', 'thesis');
	}

	protected function html_options() {
		global $thesis;
		$html = $thesis->api->html_options(array(
			'ul' => 'ul',
			'ol' => 'ol',
			'div' => 'div',
			'section' => 'section'), 'ul');
		unset($html['id'], $html['class']);
		return $html;
/*		Note: Per page setting has been disabled per WP's f-up with version 4.4.
		return array_merge($html, array(
			'per_page' => array(
				'type' => 'text',
				'width' => 'tiny',
				'label' => __('Comments Per Page', 'thesis'),
				'tooltip' => sprintf(__('The default is set in the <a href="%s">WordPress General &rarr; Discussion options</a>, but you can override that here.', 'thesis'), admin_url('options-discussion.php')),
				'default' => get_option('comments_per_page'))));
*/	}

	public function preload() {
		add_filter('comments_template', array($this, 'return_our_path'));
		if (!class_exists('thesis_comments_dummy'))
			comments_template('/comments.php', true);
		if (!empty($GLOBALS['wp_query']->comments_by_type['comment']) && !(bool)get_option('thread_comments')) {
			$GLOBALS['t_comment_counter'] = array();
			foreach ($GLOBALS['wp_query']->comments_by_type['comment'] as $number => $comment)
				$GLOBALS['t_comment_counter'][$comment->comment_ID] = $number + 1;
		}
		wp_enqueue_script('comment-reply'); #wp
	}

	public function return_our_path($path) {
		if ($path !== TEMPLATEPATH. '/comments.php')
			$this->abort = $path;
		return TEMPLATEPATH. '/comments.php';
	}

	public function html($args = array()) {
		global $thesis, $post;
		extract($args = is_array($args) ? $args : array());
		$tab = str_repeat("\t", ($this->tab_depth = !empty($depth) ? $depth : 0));
		if ($this->abort === false) {
			if (post_password_required()) {
				echo "$tab\t<p class=\"password_required\">", __('This post is password protected. Enter the password to view comments.', 'thesis'), "</p>\n";
				return;
			}
			$is_it = apply_filters('comments_template', false);
			$html = !empty($this->options['html']) ? $this->options['html'] : 'ul';
			$this->child_html = in_array($html, array('ul', 'ol')) ? 'li' : 'div';
			$hook = trim($thesis->api->esc(!empty($this->options['_id']) ?
				$this->options['_id'] : (!empty($this->options['hook']) ?
				$this->options['hook'] : '')));
			if (($comments = wp_count_comments($post->ID)) && is_object($comments) && !empty($comments->approved) && $comments->approved > 0) {
				$args = array(
					'walker' => new thesis_comment_walker,
					'callback' => array($this, 'start'),
					'type' => 'comment',
					'style' => $html);
/*				if ((bool) get_option('page_comments'))
					$args['per_page'] = (int) !empty($this->options['per_page']) ? $this->options['per_page'] : get_option('comments_per_page');
*/				!empty($hook) ? $thesis->api->hook("hook_before_$hook") : '';
				echo "$tab<$html class=\"comment_list\">\n";
				if (!in_array($html, array('ul', 'ol')))
					!empty($hook) ? $thesis->api->hook("hook_top_$hook") : '';
				wp_list_comments($args);
				if (!in_array($html, array('ul', 'ol')))
					!empty($hook) ? $thesis->api->hook("hook_bottom_$hook") : '';
				echo "$tab</$html>\n";
				!empty($hook) ? $thesis->api->hook("hook_after_$hook") : '';
			}
		}
		else
			include_once($this->abort);
	}

	public function start($comment, $args, $depth) {
		global $thesis;
		$GLOBALS['comment'] = $comment;
		echo
			str_repeat("\t", $this->tab_depth + 1),
			"<$this->child_html class=\"", esc_attr(implode(' ', get_comment_class())), "\" id=\"comment-", get_comment_ID(), "\">\n";
		$this->rotator(array('depth' => $this->tab_depth + 2));
	}
}

class thesis_comment_walker extends Walker_Comment {
	public function start_lvl(&$out, $depth = 0, $args = array()) {
		if (in_array($args['style'], array('ul', 'ol', 'div')))
			$out .= "<". esc_attr(strtolower($args['style'])). " class=\"children\">\n";
	}

	public function end_lvl(&$out, $depth = 0, $args = array()) {
		if (in_array($args['style'], array('ul', 'ol', 'div')))
			$out .= "</". esc_attr(strtolower($args['style'])). ">\n";
	}
}

class thesis_comment_author extends thesis_box {
	protected function translate() {
		$this->title = __('Comment Author', 'thesis');
	}

	protected function html_options() {
		return array(
			'author' => array(
				'type' => 'checkbox',
				'options' => array(
					'link' => __('Link comment author name', 'thesis')),
				'default' => array(
					'link' => true)));
	}

	public function html($args = array()) {
		extract($args = is_array($args) ? $args : array());
		echo
			str_repeat("\t", !empty($depth) ? $depth : 0),
			"<span class=\"comment_author\">", (isset($this->options['author']['link']) ? get_comment_author() : get_comment_author_link()), "</span>\n";
	}
}

class thesis_comment_avatar extends thesis_box {
	protected function translate() {
		$this->title = __('Comment Avatar', 'thesis');
	}

	protected function html_options() {
		global $thesis;
		return array(
			'size' => array(
				'type' => 'text',
				'width' => 'tiny',
				'label' => __($thesis->api->strings['avatar_size'], 'thesis'),
				'tooltip' => __($thesis->api->strings['avatar_tooltip'], 'thesis'),
				'description' => 'px'));
	}

	public function html($args = array()) {
		extract($args = is_array($args) ? $args : array());
		$avatar = get_avatar(get_comment_author_email(), !empty($this->options['size']) && is_numeric($this->options['size']) ? $this->options['size'] : 88);
		$author_url = get_comment_author_url();
		echo
			str_repeat("\t", !empty($depth) ? $depth : 0),
			"<span class=\"avatar\">",
			apply_filters($this->_class, empty($author_url) || $author_url == 'http://' ?
				$avatar :
				"<a href=\"$author_url\" rel=\"nofollow\">$avatar</a>"),
			"</span>\n";
	}
}

class thesis_comment_date extends thesis_box {
	protected function translate() {
		$this->title = __('Comment Date', 'thesis');
	}

	protected function html_options() {
		global $thesis;
		$html = $thesis->api->html_options();
		$html['class']['tooltip'] = sprintf(__('This box already contains a %1$as of <code>comment_date</code>. If you&#8217;d like to supply another %1$s, you can do that here.%2$s', 'thesis'), $thesis->api->base['class'], __($thesis->api->strings['class_note'], 'thesis'));
		unset($html['id']);
		return array_merge($html, array(
			'format' => array(
				'type' => 'text',
				'width' => 'short',
				'code' => true,
				'label' => __('Date Format', 'thesis'),
				'tooltip' => __($thesis->api->strings['date_tooltip'], 'thesis'),
				'default' => get_option('date_format'). ', '. get_option('time_format'))));
	}

	public function html($args = array()) {
		global $thesis;
		extract($args = is_array($args) ? $args : array());
		$format = strip_tags(!empty($this->options['format']) ?
			stripslashes($this->options['format']) :
			apply_filters("{$this->_class}_format", get_option('date_format'). ', '. get_option('time_format')));
		$date = get_comment_date(stripslashes($format));
		echo
			str_repeat("\t", !empty($depth) ? $depth : 0),
			'<span class="comment_date', (!empty($this->options['class']) ? ' '. trim($thesis->api->esc($this->options['class'])) : ''), '">',
			apply_filters($this->_class, $date, get_comment_ID()),
			"</span>\n";
	}
}

class thesis_comment_number extends thesis_box {
	protected function translate() {
		$this->title = __('Comment Number', 'thesis');
	}

	public function html($args = array()) {
		global $thesis;
		if ((bool) get_option('thread_comments')) return;
		extract($args = is_array($args) ? $args : array());
		$id = get_comment_ID();
		$number = '<span class="comment_number">'. (int) $GLOBALS['t_comment_counter'][$id]. '</span>';
		echo
			str_repeat("\t", !empty($depth) ? $depth : 0),
			apply_filters($this->_class, $number, $id), "\n";
	}
}

class thesis_comment_permalink extends thesis_box {
	protected function translate() {
		$this->title = __('Comment Permalink', 'thesis');
		$this->link = apply_filters("{$this->_class}_text", __('Link', 'thesis'));
	}

	protected function html_options() {
		return array(
			'text' => array(
				'type' => 'text',
				'width' => 'short',
				'label' => __('Comment Permalink Text', 'thesis'),
				'placeholder' => $this->link));
	}

	public function html($args = array()) {
		global $thesis;
		extract($args = is_array($args) ? $args : array());
		$text = trim(esc_html(!empty($this->options['text']) ? stripslashes($this->options['text']) : $this->link));
		echo
			str_repeat("\t", !empty($depth) ? $depth : 0),
			'<a class="comment_permalink" href="#comment-', get_comment_ID(), "\" title=\"". __($thesis->api->strings['comment_permalink'], 'thesis'). "\" rel=\"nofollow\">$text</a>\n";
	}
}

class thesis_comment_edit extends thesis_box {
	protected function translate() {
		$this->title = __('Edit Comment Link', 'thesis');
	}

	public function html($args = array()) {
		global $thesis;
		$url = get_edit_comment_link();
		if (empty($url)) return;
		extract($args = is_array($args) ? $args : array());
		echo
			str_repeat("\t", !empty($depth) ? $depth : 0),
			"<a class=\"comment_edit\" href=\"$url\" rel=\"nofollow\">", trim(esc_html(apply_filters($this->_class, strtolower(__($thesis->api->strings['edit'], 'thesis'))))), "</a>\n";
	}
}

class thesis_comment_text extends thesis_box {
	protected function translate() {
		$this->title = __('Comment Text', 'thesis');
	}

	protected function construct() {
		global $thesis;
		$thesis->wp->filter($this->_class, array(
			'wptexturize' => false,
			'convert_chars' => false,
			'make_clickable' => 9,
			'force_balance_tags' => 25,
			'convert_smilies' => 20,
			'wpautop' => 30));
	}

	protected function html_options() {
		global $thesis;
		$html = $thesis->api->html_options();
		unset($html['id']);
		return $html;
	}

	public function html($args = array()) {
		global $thesis;
		extract($args = is_array($args) ? $args : array());
		$tab = str_repeat("\t", !empty($depth) ? $depth : 0);
		echo
			"$tab<div class=\"comment_text", (!empty($this->options['class']) ? ' '. trim($thesis->api->esc($this->options['class'])) : ''), "\" id=\"comment-body-", get_comment_ID(), "\">",
			($GLOBALS['comment']->comment_approved == '0' ?
			"$tab\t<p class=\"comment_moderated\">". __('Your comment is awaiting moderation.', 'thesis'). "</p>\n" : ''),
			apply_filters($this->_class, get_comment_text()),
			"$tab</div>\n";
	}
}

class thesis_comment_reply extends thesis_box {
	protected function translate() {
		$this->title = __('Comment Reply Link', 'thesis');
		$this->text = apply_filters("{$this->_class}_text", __('Reply', 'thesis'));
	}

	protected function html_options() {
		return array(
			'text' => array(
				'type' => 'text',
				'width' => 'short',
				'label' => __('Reply Link Text', 'thesis'),
				'placeholder' => $this->text));
	}

	public function html($args = array()) {
		if (!get_option('thread_comments')) return;
		extract($args = is_array($args) ? $args : array());
		echo str_repeat("\t", !empty($depth) ? $depth : 0), get_comment_reply_link(array(
			'add_below' => 'comment-body',
			'respond_id' => 'commentform',
			'reply_text' => trim(esc_html(!empty($this->options['text']) ? stripslashes($this->options['text']) : $this->text)),
			'login_text' => __('Log in to reply', 'thesis'),
			'depth' => $GLOBALS['comment_depth'],
			'before' => apply_filters("{$this->_class}_before", ''),
			'after' => apply_filters("{$this->_class}_after", ''),
			'max_depth' => (int) get_option('thread_comments_depth'))), "\n";
	}
}

class thesis_comment_form extends thesis_box {
	public $type = 'rotator';
	public $dependents = array(
		'thesis_comment_form_title',
		'thesis_comment_form_cancel',
		'thesis_comment_form_name',
		'thesis_comment_form_email',
		'thesis_comment_form_url',
		'thesis_comment_form_comment',
		'thesis_comment_form_submit');
	public $children = array(
		'thesis_comment_form_title',
		'thesis_comment_form_cancel',
		'thesis_comment_form_name',
		'thesis_comment_form_email',
		'thesis_comment_form_url',
		'thesis_comment_form_comment',
		'thesis_comment_form_submit');
	public $templates = array('single', 'page');

	protected function translate() {
		$this->title = $this->name = __('Comment Form', 'thesis');
	}

	public function html($args = array()) {
		global $thesis, $user_ID, $post, $commenter; #wp
		if (!comments_open()) return;
		extract($args = is_array($args) ? $args : array());
		$tab = str_repeat("\t", $depth = !empty($depth) ? $depth : 0);
		$hook = trim($thesis->api->esc(!empty($this->options['_id']) ? $this->options['_id'] : 'comment_form'));
		if (get_option('comment_registration') && !!!$user_ID) #wp
			echo
				"$tab<p class=\"login_alert\">",
				__('You must log in to post a comment.', 'thesis'),
				" <a href=\"", wp_login_url(get_permalink()), "\" rel=\"nofollow\">", __('Log in now.', 'thesis'),"</a></p>\n";
		else {
			$commenter = wp_get_current_commenter();
			!empty($hook) ? $thesis->api->hook("hook_before_$hook") : '';
			echo
				"$tab<div id=\"commentform\">\n",
				"$tab\t<form method=\"post\" action=\"", site_url('wp-comments-post.php'), "\">\n"; #wp
			!empty($hook) ? $thesis->api->hook("hook_top_$hook") : '';
			$this->rotator(array_merge($args, array('depth' => $depth + 2, 'req' => get_option('require_name_email'))));
			!empty($hook) ? $thesis->api->hook("hook_bottom_$hook") : '';
			do_action('comment_form', $post->ID); #wp
			comment_id_fields(); #wp
			echo
				"$tab\t</form>\n",
				"$tab</div>\n";
			!empty($hook) ? $thesis->api->hook("hook_after_$hook") : '';
		}
	}
}

class thesis_comment_form_title extends thesis_box {
	protected function translate() {
		$this->title = __('Comment Form Title', 'thesis');
		$this->leave = apply_filters("{$this->_class}_text", __('Leave a Comment', 'thesis'));
	}

	protected function html_options() {
		return array(
			'title' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => $this->title,
				'placeholder' => $this->leave));
	}

	public function html($args = array()) {
		global $thesis;
		extract($args = is_array($args) ? $args : array());
		$title = !empty($this->options['title']) ?
			stripslashes($this->options['title']) :
			$this->leave;
		echo
			str_repeat("\t", !empty($depth) ? $depth : 0),
			"<p class=\"comment_form_title\">",
			trim($thesis->api->escht(apply_filters($this->_class, $title))),
			"</p>\n";
	}
}

class thesis_comment_form_name extends thesis_box {
	protected function translate() {
		$this->title = __('Name Input', 'thesis');
	}

	protected function html_options() {
		global $thesis;
		return array(
			'label' => array(
				'type' => 'checkbox',
				'options' => array(
					'show' => __($thesis->api->strings['show_label'], 'thesis')),
				'default' => array(
					'show' => true)),
			'placeholder' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __($thesis->api->strings['placeholder'], 'thesis'),
				'tooltip' => __($thesis->api->strings['placeholder_tooltip'], 'thesis')));
	}

	public function html($args = array()) {
		global $thesis, $user_ID, $user_identity, $commenter, $req;
		extract($args = is_array($args) ? $args : array());
		$tab = str_repeat("\t", !empty($depth) ? $depth : 0);
		if (!!$user_ID) // This should probably be moved to the comment form box to safeguard against unwanted display outcomes
			echo
				"$tab<p>", __('Logged in as', 'thesis'), ' <a href="', admin_url('profile.php'), "\" rel=\"nofollow\">$user_identity</a>. ",
				'<a href="', wp_logout_url(get_permalink()), '" rel="nofollow">', __('Log out &rarr;', 'thesis'), "</a></p>\n";
		else
			echo
				"$tab<p id=\"comment_form_name\">\n",
				(isset($this->options['label']['show']) ? '' :
				"$tab\t<label for=\"author\">". __($thesis->api->strings['name'], 'thesis'). "". (!!$req ? " <span class=\"required\" title=\"". __($thesis->api->strings['required'], 'thesis'). "\">*</span>" : ''). "</label>\n"),
				"$tab\t<input type=\"text\" id=\"author\" class=\"input_text\" name=\"author\" value=\"", esc_attr($commenter['comment_author']), '" ',
				(!empty($this->options['placeholder']) ?
				'placeholder="'. trim($thesis->api->esc($this->options['placeholder'])). '" ' : ''),
				'tabindex="1"', ($req ? ' aria-required="true"' : ''), " />\n",
				"$tab</p>\n";
	}
}

class thesis_comment_form_email extends thesis_box {
	protected function translate() {
		$this->title = __('Email Input', 'thesis');
	}

	protected function html_options() {
		global $thesis;
		return array(
			'label' => array(
				'type' => 'checkbox',
				'options' => array(
					'show' => __($thesis->api->strings['show_label'], 'thesis')),
				'default' => array(
					'show' => true)),
			'placeholder' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __($thesis->api->strings['placeholder'], 'thesis'),
				'tooltip' => __($thesis->api->strings['placeholder_tooltip'], 'thesis')));
	}

	public function html($args = array()) {
		global $thesis, $user_ID, $commenter, $req;
		if (!!$user_ID) return;
		extract($args = is_array($args) ? $args : array());
		$tab = str_repeat("\t", !empty($depth) ? $depth : 0);
		echo
			"$tab<p id=\"comment_form_email\">\n",
			(isset($this->options['label']['show']) ? '' :
			"$tab\t<label for=\"email\">". __($thesis->api->strings['email'], 'thesis'). "". (!!$req ? " <span class=\"required\" title=\"". esc_attr(__($thesis->api->strings['required'], 'thesis')). "\">*</span>" : ''). "</label>\n"),
			"$tab\t<input type=\"text\" id=\"email\" class=\"input_text\" name=\"email\" value=\"", esc_attr($commenter['comment_author_email']), '" ',
			(!empty($this->options['placeholder']) ?
			'placeholder="'. trim($thesis->api->esc($this->options['placeholder'])). '" ' : ''),
			'tabindex="2"', (!!$req ? ' aria-required="true"' : ''), " />\n",
			"$tab</p>\n";
	}
}

class thesis_comment_form_url extends thesis_box {
	protected function translate() {
		global $thesis;
		$this->title = sprintf(__('%s Input', 'thesis'), $thesis->api->base['url']);
	}

	protected function html_options() {
		global $thesis;
		return array(
			'label' => array(
				'type' => 'checkbox',
				'options' => array(
					'show' => __($thesis->api->strings['show_label'], 'thesis')),
				'default' => array(
					'show' => true)),
			'placeholder' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __($thesis->api->strings['placeholder'], 'thesis'),
				'tooltip' => __($thesis->api->strings['placeholder_tooltip'], 'thesis')));
	}

	public function html($args = array()) {
		global $thesis, $user_ID, $commenter;
		if (!!$user_ID) return;
		extract($args = is_array($args) ? $args : array());
		$tab = str_repeat("\t", !empty($depth) ? $depth : 0);
		echo
			"$tab<p id=\"comment_form_url\">\n",
			(isset($this->options['label']['show']) ? '' :
			"$tab\t<label for=\"url\">". __($thesis->api->strings['website'], 'thesis'). "</label>\n"),
			"$tab\t<input type=\"text\" id=\"url\" class=\"input_text\" name=\"url\" value=\"", esc_attr($commenter['comment_author_url']), '" ',
			(!empty($this->options['placeholder']) ?
			'placeholder="'. trim($thesis->api->esc($this->options['placeholder'])). '" ' : ''),
			"tabindex=\"3\" />\n",
			"$tab</p>\n";
	}
}

class thesis_comment_form_comment extends thesis_box {
	protected function translate() {
		$this->title = __('Comment Input', 'thesis');
	}

	protected function html_options() {
		global $thesis;
		return array(
			'label' => array(
				'type' => 'checkbox',
				'options' => array(
					'show' => __($thesis->api->strings['show_label'], 'thesis')),
				'default' => array(
					'show' => true)),
			'rows' => array(
				'type' => 'text',
				'width' => 'tiny',
				'label' => __('Number of Rows in Comment Input Box', 'thesis'),
				'tooltip' => __('The number of rows determines the height of the comment input box. The higher the number, the taller the input box.', 'thesis'),
				'default' => 6));
	}

	public function html($args = array()) {
		global $thesis;
		extract($args = is_array($args) ? $args : array());
		$tab = str_repeat("\t", !empty($depth) ? $depth : 0);
		$rows = !empty($this->options['rows']) && is_numeric($this->options['rows']) ? (int) $this->options['rows'] : 6;
		echo
			"$tab<p id=\"comment_form_comment\">\n",
			(isset($this->options['label']['show']) ? '' :
			"$tab\t<label for=\"comment\">". __($thesis->api->strings['comment'], 'thesis'). "</label>\n"),
			"$tab\t<textarea name=\"comment\" id=\"comment\" class=\"input_text\" tabindex=\"4\" rows=\"$rows\"></textarea>\n",
			"$tab</p>\n";
	}
}

class thesis_comment_form_submit extends thesis_box {
	protected function translate() {
		$this->title = __('Submit Button', 'thesis');
	}

	protected function html_options() {
		global $thesis;
		return array(
			'text' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __($thesis->api->strings['submit_button_text'], 'thesis'),
				'placeholder' => __($thesis->api->strings['submit'], 'thesis')));
	}

	public function html($args = array()) {
		global $thesis;
		extract($args = is_array($args) ? $args : array());
		$tab = str_repeat("\t", !empty($depth) ? $depth : 0);
		$value = trim(esc_attr(!empty($this->options['text']) ? stripslashes($this->options['text']) : __($thesis->api->strings['submit'], 'thesis')));
		echo
			"$tab<p id=\"comment_form_submit\">\n",
			"$tab\t<input type=\"submit\" id=\"submit\" class=\"input_submit\" name=\"submit\" tabindex=\"5\" value=\"$value\" />\n",
			"$tab</p>\n";
	}
}

class thesis_comment_form_cancel extends thesis_box {
	protected function translate() {
		$this->title = __('Cancel Reply Link', 'thesis');
		$this->cancel = apply_filters("{$this->_class}_text", __('Cancel reply', 'thesis'));
	}

	protected function html_options() {
		return array(
			'text' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __('Cancel Link Text', 'thesis'),
				'placeholder' => $this->cancel));
	}

	public function html($args = array()) {
		extract($args = is_array($args) ? $args : array());
		echo str_repeat("\t", !empty($depth) ? $depth : 0);
		cancel_comment_reply_link(esc_attr(!empty($this->options['text']) ? stripslashes($this->options['text']) : $this->cancel)); #wp
		echo "\n";
	}
}

class thesis_trackbacks extends thesis_box {
	public $type = 'rotator';
	public $dependents = array(
		'thesis_comment_author',
		'thesis_comment_date',
		'thesis_comment_text');
	public $children = array(
		'thesis_comment_author',
		'thesis_comment_date',
		'thesis_comment_text');
	public $templates = array('single', 'page');

	protected function translate() {
		$this->title = $this->name = __('Trackbacks', 'thesis');
	}

	public function preload() {
		if (!class_exists('thesis_comments_dummy'))
			comments_template('/comments.php', true);
	}

	public function html($args = array()) {
		global $thesis, $wp_query;
		extract($args = is_array($args) ? $args : array());
		$tab = str_repeat("\t", $depth = !empty($depth) ? $depth : 0);
		if (empty($wp_query->comments_by_type)) // separate the comments and put them in wp_query if they aren't there already
			$wp_query->comments_by_type = &separate_comments($wp_query->comments);
		foreach ($wp_query->comments as $a)
			if ($a->comment_type == 'pingback' || $a->comment_type == 'trackback')
				$b[] = $a;
		if (empty($b)) return;
		$hook = trim($thesis->api->esc(!empty($this->options['_id']) ? $this->options['_id'] : ''));
		!empty($hook) ? $thesis->api->hook("hook_before_$hook") : '';
		echo "$tab<ul id=\"trackback_list\">\n";
		foreach ($b as $t) {
			$GLOBALS['comment'] = $t;
			echo "$tab\t<li>";
			$this->rotator(array_merge($args, array('depth' => $depth + 1, 't' => $t)));
			echo "</li>\n";
		}
		echo "$tab</ul>\n";
		!empty($hook) ? $thesis->api->hook("hook_after_$hook") : '';
	}
}

class thesis_previous_post_link extends thesis_box {
	public $templates = array('single');

	protected function translate() {
		$this->title = __('Previous Post Link', 'thesis');
	}

	protected function html_options() {
		global $thesis;
		$html = $thesis->api->html_options(array('div' => 'div', 'span' => 'span', 'p' => 'p'), 'span');
		unset($html['id'], $html['class']);
		return array_merge($html, array(
			'intro' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __($thesis->api->strings['intro_text'], 'thesis'),
				'placeholder' => __('Previous Post:', 'thesis')),
			'link' => array(
				'type' => 'radio',
				'label' => __($thesis->api->strings['link_text'], 'thesis'),
				'options' => array(
					'title' => __($thesis->api->strings['use_post_title'], 'thesis'),
					'custom' => __($thesis->api->strings['use_custom_text'], 'thesis')),
				'default' => 'title',
				'dependents' => array('custom')),
			'text' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __($thesis->api->strings['custom_link_text'], 'thesis'),
				'parent' => array(
					'link' => 'custom'))));
	}

	public function html($args = array()) {
		global $thesis, $wp_query;
		if (!$wp_query->is_single || !get_previous_post()) return;
		extract($args = is_array($args) ? $args : array());
		$html = !empty($this->options['html']) ? $this->options['html'] : 'span';
		echo str_repeat("\t", !empty($depth) ? $depth : 0), "<$html class=\"previous_post\">";
		previous_post_link((!empty($this->options['intro']) ? trim($thesis->api->escht($this->options['intro'], true)) . ' ' : '') . '%link', !empty($this->options['link']) && $this->options['link'] == 'custom' ? (!empty($this->options['text']) ? trim($thesis->api->escht($this->options['text'], true)) : '%title') : '%title'); #wp
		echo "</$html>\n";
	}
}

class thesis_next_post_link extends thesis_box {
	public $templates = array('single');

	protected function translate() {
		$this->title = __('Next Post Link', 'thesis');
	}

	protected function html_options() {
		global $thesis;
		$html = $thesis->api->html_options(array('div' => 'div', 'span' => 'span', 'p' => 'p'), 'span');
		unset($html['id'], $html['class']);
		return array_merge($html, array(
			'intro' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __($thesis->api->strings['intro_text'], 'thesis'),
				'placeholder' => __('Next Post:', 'thesis')),
			'link' => array(
				'type' => 'radio',
				'label' => __($thesis->api->strings['link_text'], 'thesis'),
				'options' => array(
					'title' => __($thesis->api->strings['use_post_title'], 'thesis'),
					'custom' => __($thesis->api->strings['use_custom_text'], 'thesis')),
				'default' => 'title',
				'dependents' => array('custom')),
			'text' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __($thesis->api->strings['custom_link_text'], 'thesis'),
				'parent' => array(
					'link' => 'custom'))));
	}

	public function html($args = array()) {
		global $thesis, $wp_query;
		if (!$wp_query->is_single || !get_next_post()) return;
		extract($args = is_array($args) ? $args : array());
		$html = !empty($this->options['html']) ? $this->options['html'] : 'span';
		echo str_repeat("\t", !empty($depth) ? $depth : 0), "<$html class=\"next_post\">";
		next_post_link((!empty($this->options['intro']) ? trim($thesis->api->escht($this->options['intro'], true)) . ' ' : '') . '%link', !empty($this->options['link']) && $this->options['link'] == 'custom' ? (!empty($this->options['text']) ? trim($thesis->api->escht($this->options['text'], true)) : '%title') : '%title'); #wp
		echo "</$html>\n";
	}
}

class thesis_previous_posts_link extends thesis_box {
	public $templates = array('home', 'archive');

	protected function translate() {
		$this->previous = __('Previous Posts', 'thesis');
		$this->title = __('Previous Posts Link', 'thesis');
	}

	protected function html_options() {
		global $thesis;
		$html = $thesis->api->html_options(array('div' => 'div', 'span' => 'span', 'p' => 'p'), 'span');
		unset($html['id'], $html['class']);
		return array_merge($html, array(
			'text' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __($thesis->api->strings['link_text'], 'thesis'),
				'placeholder' => $this->previous,
				'description' => __($thesis->api->strings['no_html'], 'thesis'))));
	}

	public function html($args = array()) {
		global $thesis, $wp_query; #wp
		if (!(($wp_query->is_home || $wp_query->is_archive || $wp_query->is_search) && $wp_query->max_num_pages > 1 && ((!empty($wp_query->query_vars['paged']) ? $wp_query->query_vars['paged'] : 1) < $wp_query->max_num_pages))) return;
		extract($args = is_array($args) ? $args : array());
		$html = !empty($this->options['html']) ? $this->options['html'] : 'span';
		echo
			str_repeat("\t", !empty($depth) ? $depth : 0), "<$html class=\"previous_posts\">",
			get_next_posts_link(trim($thesis->api->escht(apply_filters($this->_class, !empty($this->options['text']) ? stripslashes($this->options['text']) : $this->previous)))),
			"</$html>\n";
	}
}

class thesis_next_posts_link extends thesis_box {
	public $templates = array('home', 'archive');

	protected function translate() {
		$this->next = __('Next Posts', 'thesis');
		$this->title = sprintf(__('%s Link', 'thesis'), $this->next);
	}

	protected function html_options() {
		global $thesis;
		$html = $thesis->api->html_options(array('div' => 'div', 'span' => 'span', 'p' => 'p'), 'span');
		unset($html['id'], $html['class']);
		return array_merge($html, array(
			'text' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __($thesis->api->strings['link_text'], 'thesis'),
				'placeholder' => $this->next,
				'description' => __($thesis->api->strings['no_html'], 'thesis'))));
	}

	public function html($args = array()) {
		global $thesis, $wp_query; #wp
		if (!(($wp_query->is_home || $wp_query->is_archive || $wp_query->is_search) && $wp_query->max_num_pages > 1 && ((!empty($wp_query->query_vars['paged']) ? $wp_query->query_vars['paged'] : 1) > 1))) return;
		extract($args = is_array($args) ? $args : array());
		$html = !empty($this->options['html']) ? $this->options['html'] : 'span';
		echo
			str_repeat("\t", !empty($depth) ? $depth : 0), "<$html class=\"next_posts\">",
			get_previous_posts_link(trim($thesis->api->escht(apply_filters($this->_class, !empty($this->options['text']) ? stripslashes($this->options['text']) : $this->next)))),
			"</$html>\n";
	}
}

class thesis_wp_widgets extends thesis_box {
	private $tag = false;

	protected function translate() {
		$this->title = $this->name = __('Widgets', 'thesis');
	}

	protected function construct() {
		global $thesis;
		$this->tag = ($html = apply_filters("{$this->_class}_html", 'div')) && in_array($html, array('div', 'li', 'article', 'section')) ?
			$html : 'div';
		$title_tag = ($title_html = apply_filters("{$this->_class}_title_html", !empty($this->options['title_tag']) ? $this->options['title_tag'] : 'p')) && in_array($title_html, array('h1', 'h2', 'h3', 'h4', 'h5', 'p')) ?
			$title_html : 'p';
		register_sidebar(array(
			'name' => $this->name,
			'id' => $this->_id,
			'before_widget' => "<$this->tag class=\"widget %2\$s" . (!empty($this->options['class']) ? ' ' . trim($thesis->api->esc($this->options['class'])) : '') . '" id="%1$s">',
			'after_widget' => "</$this->tag>",
			'before_title' => "<$title_tag class=\"widget_title\">",
			'after_title' => "</$title_tag>"));
	}

	protected function html_options() {
		global $thesis;
		$html = $thesis->api->html_options();
		unset($html['id']);
		return array_merge($html, array(
			'title_tag' => array(
				'type' => 'select',
				'label' => sprintf(__('Widget Title %s', 'thesis'), __($thesis->api->strings['html_tag'], 'thesis')),
				'options' => array(
					'h2' => 'h2',
					'h3' => 'h3',
					'h4' => 'h4',
					'h5' => 'h5',
					'p' => 'p'),
				'default' => 'p'),
			'div_wrap' => array(
				'type' => 'checkbox',
				'label' => __('Show Wrapping Element', 'thesis'),
				'options' => array('show' => __('Wrap widget with an HTML element.', 'thesis')),
				'dependents' => array('show'),
				'default' => array('show' => false)),
			'div' => array(
				'type' => 'select',
				'label' => __('Choose Wrapping Element', 'thesis'),
				'options' => array('div' => 'div', 'section' => 'section'),
				'parent' => array('div_wrap' => 'show')),
			'div_id' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __('Wrapping Element HTML id', 'thesis'),
				'parent' => array('div_wrap' => 'show')),
			'div_class' => array(
				'type' => 'text',
				'width' => 'medium',
				'label' => __('Wrapping Element HTML class', 'thesis'),
				'parent' => array('div_wrap' => 'show'))));
	}

	public function html($args = array()) {
		global $thesis;
		if (!is_user_logged_in() && !is_active_sidebar($this->_id))
			return;
		extract($args = is_array($args) ? $args : array());
		$tab = str_repeat("\t", !empty($depth) ? $depth : 0);
		$hook = !empty($this->options['_id']) ? trim($thesis->api->esc($this->options['_id'])) : '';
		if (!empty($this->options['div_wrap']['show']))
			echo
				"$tab<", esc_attr($this->options['div']),
				(!empty($this->options['div_id']) ? ' id="'. esc_attr($this->options['div_id']). '" ' : ''),
				' class="', (!empty($this->options['div_class']) ? esc_attr($this->options['div_class']) : 'widget_wrap'), '"',
				">";
		if ($list = $this->tag == 'li' ? true : false)
			echo "$tab\t<ul". (($class = apply_filters("{$this->_class}_ul_class", 'widget_list')) ? ' class="'. trim(esc_attr($class)). '"' : ''). ">\n";
		!empty($hook) ? $thesis->api->hook("hook_{$hook}_first") : '';
		if (!dynamic_sidebar($this->_id) && is_user_logged_in() && ($text = apply_filters("{$this->_class}_{$hook}_default_text", sprintf(__('This is a widget box named %1$s, but there are no widgets in it yet. <a href="%2$s">Add a widget here</a>. (And don&#8217;t worry—your visitors cannot see this text.)', 'thesis'), $this->name, admin_url('widgets.php')))))
			echo
				"$tab\t\t<$this->tag class=\"widget", (!empty($this->options['class']) ? ' '. trim($thesis->api->esc($this->options['class'])) : ''), "\">\n",
				"$tab\t\t\t<p>$text</p>\n",
				"$tab\t\t</$this->tag>\n";
		!empty($hook) ? $thesis->api->hook("hook_{$hook}_last") : '';
		if ($list)
			echo "\n$tab\t</ul>\n";
		if (!empty($this->options['div_wrap']['show']))
			echo "</", esc_attr($this->options['div']), ">\n";
	}
}

class thesis_wp_admin extends thesis_box {
	protected function translate() {
		global $thesis;
		$this->title = sprintf(__('%s Admin Link', 'thesis'), $thesis->api->base['wp']);
	}

	public function html($args = array()) {
		global $thesis;
		extract($args = is_array($args) ? $args : array());
		echo str_repeat("\t", !empty($depth) ? $depth : 0),
			"<p><a href=\"", admin_url(), '">', sprintf(__('%s Admin', 'thesis'), $thesis->api->base['wp']), "</a></p>\n"; #wp
	}
}