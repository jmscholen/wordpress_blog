<?php
/*
Name: Classic Responsive
Author: Chris Pearson
Description: Elegant and versatile, the Classic Responsive Skin features clean lines and mathematical precision with an emphasis on typography.
Version: 1.3
Reqires: 2.2.3
Class: thesis_classic_r
License: MIT

Copyright 2013 DIYthemes, LLC.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
class thesis_classic_r extends thesis_skin {
	/*
	Skin API $functionality array. Enable or disable certain Skin features with ease.
	— http://diythemes.com/thesis/rtfm/api/skin/properties/functionality/
	*/
	public $functionality = array(
		'css_preprocessor' => 'scss',
		'editor_grt' => true,
		'fonts_google' => true,
		'header_image' => true,
		'logo' => true);

	/*
	Skin API pseudo-constructor; place hooks, filters, and other initializations here.
	— http://diythemes.com/thesis/rtfm/api/skin/methods/construct/
	*/
	protected function construct() {
		// implement display options that do not follow the normal pattern
		if (!empty($this->display['misc']['display']['braces'])) {
			add_filter('thesis_post_num_comments', array($this, 'num_comments'));
			add_filter('thesis_comments_intro', array($this, 'comments_intro'));
		}
		// the previous/next links (found on home, archive, and single templates) require special filtering based on page context
		add_filter('thesis_html_container_prev_next_show', array($this, 'prev_next'));
		// hook header image into the proper location for this Skin
		add_action('hook_bottom_header', array($this, 'header_image'));
	}

/*---:[ Implement non-standard display options ]:---*/

	/*
	The following is a special filter to prevent the prev/next container from showing if the query only has 1 page of results.
	*/
	public function prev_next() {
		global $wp_query;
		return (($wp_query->is_home || $wp_query->is_archive || $wp_query->is_search) && $wp_query->max_num_pages > 1) || ($wp_query->is_single && !empty($this->display['misc']['display']['prev_next'])) ? true : false;
	}

	public function num_comments($content) {
		return "<span class=\"bracket\">{</span> $content <span class=\"bracket\">}</span>";
	}

	public function comments_intro($text) {
		return "<span class=\"bracket\">{</span> $text <span class=\"bracket\">}</span>";
	}

/*---:[ Implement a user-added Header Image ]:---*/

	/*
	Output user header image by referencing the Skin API header image object and its associated html() method.
	— http://diythemes.com/thesis/rtfm/api/skin/properties/functionality/header-image/
	*/
	public function header_image() {
		$this->header_image->html();
	}

	/*
	Skin API method for filtering the CSS output whenever the stylesheet is rewritten.
	In this case, we are adding some CSS if the user has selected a header image.
	— http://diythemes.com/thesis/rtfm/api/skin/methods/filter_css/
	*/
	public function filter_css($css) {
		return $css.
		(!empty($this->logo->image) ?
			"\n#site_title {\n".
			"\tline-height: 0.1em;\n".
			"}\n".
			"\n#site_title a {\n".
			"\tdisplay: inline-block;\n".
			"}\n" : '').
		(!empty($this->header_image->image) ?
			"\n#header {\n".
			"\tpadding: 0;\n".
			"}\n".
			"#header #site_title a, #header #site_tagline {\n".
			"\tdisplay: none;\n".
			"}\n" : '');
	}

/*---:[ Skin Display Options ]:---*/

	/*
	Skin API method for initiating display options; return an array in Thesis Options API array format.
	— Display options: http://diythemes.com/thesis/rtfm/api/skin/display-options/
	— Options API array format: http://diythemes.com/thesis/rtfm/api/options/array-format/
	*/
	protected function display() {
		return array( // use an options object set for simplified display controls
			'display' => array(
				'type' => 'object_set',
				'select' => __('Select content to display:', 'thesis_classic_r'),
				'objects' => array(
					'site' => array(
						'type' => 'object',
						'label' => __('Site Title &amp; Tagline', 'thesis_classic_r'),
						'fields' => array(
							'display' => array(
								'type' => 'checkbox',
								'options' => array(
									'title' => __('Site title', 'thesis_classic_r'),
									'tagline' => __('Site tagline', 'thesis_classic_r')),
								'default' => array(
									'title' => true,
									'tagline' => true)))),
					'loop' => array(
						'type' => 'object',
						'label' => __('Post/Page Output', 'thesis_classic_r'),
						'fields' => array(
							'display' => array(
								'type' => 'checkbox',
								'options' => array(
									'author' => __('Author', 'thesis_classic_r'),
									'avatar' => __('Author avatar', 'thesis_classic_r'),
									'description' => __('Author description (Single template)', 'thesis_classic_r'),
									'date' => __('Date', 'thesis_classic_r'),
									'wp_featured_image' => __('WP featured image', 'thesis_classic_r'),
									'image' => __('Thesis post image (Single, Page, and Landing Page templates)', 'thesis_classic_r'),
									'thumbnail' => __('Thesis thumbnail image (Home template)', 'thesis_classic_r'),
									'num_comments' => __('Number of comments (Home and Archive templates)', 'thesis_classic_r'),
									'cats' => __('Categories', 'thesis_classic_r'),
									'tags' => __('Tags', 'thesis_classic_r')),
								'default' => array(
									'author' => true,
									'date' => true,
									'wp_featured_image' => true,
									'num_comments' => true)))),
					'comments' => array(
						'type' => 'object',
						'label' => __('Comments', 'thesis_classic_r'),
						'fields' => array(
							'display' => array(
								'type' => 'checkbox',
								'options' => array(
									'post' => __('Comments on posts', 'thesis_classic_r'),
									'page' => __('Comments on pages', 'thesis_classic_r'),
									'date' => __('Comment date', 'thesis_classic_r'),
									'avatar' => __('Comment avatar', 'thesis_classic_r')),
								'default' => array(
									'post' => true,
									'date' => true,
									'avatar' => true)))),
					'sidebar' => array(
						'type' => 'object',
						'label' => __('Sidebar', 'thesis_classic_r'),
						'fields' => array(
							'display' => array(
								'type' => 'checkbox',
								'options' => array(
									'sidebar' => __('Sidebar', 'thesis_classic_r'),
									'text' => __('Sidebar Text Box', 'thesis_classic_r'),
									'widgets' => __('Sidebar Widgets', 'thesis_classic_r')),
								'default' => array(
									'sidebar' => true,
									'text' => true,
									'widgets' => true)))),
					'misc' => array(
						'type' => 'object',
						'label' => __('Miscellaneous', 'thesis_classic_r'),
						'fields' => array(
							'display' => array(
								'type' => 'checkbox',
								'options' => array(
									'braces' => __('Iconic Classic Responsive Skin curly braces', 'thesis_classic_r'),
									'prev_next' => __('Previous/next post links (single template)', 'thesis_classic_r'),
									'attribution' => __('Skin attribution', 'thesis_classic_r'),
									'wp_admin' => __('WP admin link', 'thesis_classic_r')),
								'default' => array(
									'braces' => true,
									'prev_next' => true,
									'attribution' => true,
									'wp_admin' => true)))))));
	}

	/*
	Skin API method for automatic show/hide handling of elements with display options.
	Display options are defined in the display() method below.
	— http://diythemes.com/thesis/rtfm/api/skin/methods/display_elements/
	*/
	public function display_elements() {
		return array( // Display options with filter references
			'site' => array(
				'title' => 'thesis_site_title',
				'tagline' => 'thesis_site_tagline'),
			'loop' => array( // 'loop' has been added as a programmatic ID to these Boxes
				'author' => 'thesis_post_author_loop',
				'avatar' => 'thesis_post_author_avatar_loop',
				'description' => 'thesis_post_author_description_loop',
				'date' => 'thesis_post_date_loop',
				'wp_featured_image' => 'thesis_wp_featured_image_loop',
				'cats' => 'thesis_post_categories_loop',
				'tags' => 'thesis_post_tags_loop',
				'num_comments' => 'thesis_post_num_comments_loop',
				'image' => 'thesis_post_image_loop',
				'thumbnail' => 'thesis_post_thumbnail_loop'),
			'comments' => array( // 'comments' has been added as a programmatic ID to the date and avatar Boxes
				'post' => 'thesis_html_container_post_comments',
				'page' => 'thesis_html_container_page_comments',
				'date' => 'thesis_comment_date_comments',
				'avatar' => 'thesis_comment_avatar_comments'),
			'sidebar' => array( // 'sidebar' is the hook name for 'sidebar' and the programmatic ID for text and widgets
				'sidebar' => 'thesis_html_container_sidebar',
				'text' => 'thesis_text_box_sidebar',
				'widgets' => 'thesis_wp_widgets_sidebar'),
			'misc' => array(
				'attribution' => 'thesis_attribution',
				'wp_admin' => 'thesis_wp_admin'));
	}

/*---:[ Skin Design Options ]:---*/

	/*
	Skin API method for initiating design options; return an array in Thesis Options API array format.
	— Design options: http://diythemes.com/thesis/rtfm/api/skin/design-options/
	— Options API array format: http://diythemes.com/thesis/rtfm/api/options/array-format/
	*/
	protected function design() {
		$css = $this->css_tools->options; // shorthand for all options available in the CSS API
		$fsc = $nav = $this->css_tools->font_size_color(); // the CSS API contains shorthand for font, size, and color options
		unset($nav['color']); // remove nav text color control
		$links['default'] = 'DD0000'; // default link color
		$links['gray'] = $this->color->gray($links['default']); // array of 'hex' and 'rgb' values
		return array(
			'colors' => $this->color_scheme(array( // the Skin API contains a color_scheme() method for easy implementation
				'id' => 'colors',
				'colors' => array(
					'text1' => __('Primary Text', 'thesis_classic_r'),
					'text2' => __('Secondary Text', 'thesis_classic_r'),
					'links' => __('Links', 'thesis_classic_r'),
					'color1' => __('Borders &amp; Highlights', 'thesis_classic_r'),
					'color2' => __('Interior <abbr title="background">BG</abbr>s', 'thesis_classic_r'),
					'color3' => __('Site <abbr title="background">BG</abbr>', 'thesis_classic_r')),
				'default' => array(
					'text1' => '111111',
					'text2' => '888888',
					'links' => $links['default'],
					'color1' => 'DDDDDD',
					'color2' => 'EEEEEE',
					'color3' => 'FFFFFF'),
				'scale' => array(
					'links' => $links['gray']['hex'],
					'color1' => 'DDDDDD',
					'color2' => 'EEEEEE',
					'color3' => 'FFFFFF'))),
			'elements' => array( // this is an object set containing all other design options for this Skin
				'type' => 'object_set',
				'label' => __('Layout, Fonts, Sizes, and Colors', 'thesis_classic_r'),
				'select' => __('Select a design element to edit:', 'thesis_classic_r'),
				'objects' => array(
					'layout' => array(
						'type' => 'object',
						'label' => __('Layout &amp; Dimensions', 'thesis_classic_r'),
						'fields' => array(
							'columns' => array(
								'type' => 'select',
								'label' => __('Layout', 'thesis_classic_r'),
								'options' => array(
									1 => __('1 column', 'thesis_classic_r'),
									2 => __('2 columns', 'thesis_classic_r')),
								'default' => 2,
								'dependents' => array(2)),
							'order' => array(
								'type' => 'radio',
								'options' => array(
									'' => __('Content on the left', 'thesis_classic_r'),
									'right' => __('Content on the right', 'thesis_classic_r')),
								'parent' => array(
									'columns' => 2)),
							'width-content' => array(
								'type' => 'text',
								'width' => 'tiny',
								'label' => __('Content Width', 'thesis_classic_r'),
								'tooltip' => __('The default content column width is 617px. The value you enter here is the entire width of the column, including padding and borders. The resulting width of your text in this column is based on your selected font and font size. We recommend using Chrome Developer Tools or Firebug for Firefox to inspect the text width if you need to achieve a precise value.', 'thesis_classic_r'),
								'description' => 'px',
								'default' => 617),
							'width-sidebar' => array(
								'type' => 'text',
								'width' => 'tiny',
								'label' => __('Sidebar Width', 'thesis_classic_r'),
								'tooltip' => __('The default sidebar column width is 280px. The value you enter here is the entire width of the column, including padding. The resulting width of your text in this column is based on your selected font and font size. We recommend using Chrome Developer Tools or Firebug for Firefox to inspect the text width if you need to achieve a precise value.', 'thesis_classic_r'),
								'description' => 'px',
								'default' => 280,
								'parent' => array(
									'columns' => 2)))),
					'font' => array(
						'type' => 'object',
						'label' => __('Font &amp; Size (Primary)', 'thesis_classic_r'),
						'fields' => array(
							'family' => array_merge($css['font']['fields']['font-family'], array('default' => 'georgia')),
							'size' => array_merge($css['font']['fields']['font-size'], array('default' => 16)))),
					'headline' => array(
						'type' => 'group',
						'label' => __('Headlines', 'thesis_classic_r'),
						'fields' => $fsc),
					'subhead' => array(
						'type' => 'group',
						'label' => __('Sub-headlines', 'thesis_classic_r'),
						'fields' => $fsc),
					'blockquote' => array(
						'type' => 'group',
						'label' => __('Blockquotes', 'thesis_classic_r'),
						'fields' => $fsc),
					'code' => array(
						'type' => 'group',
						'label' => __('Code: Inline &lt;code&gt;', 'thesis_classic_r'),
						'fields' => $fsc),
					'pre' => array(
						'type' => 'group',
						'label' => __('Code: Pre-formatted &lt;pre&gt;', 'thesis_classic_r'),
						'fields' => $fsc),
					'title' => array(
						'type' => 'object',
						'label' => __('Site Title', 'thesis_classic_r'),
						'fields' => $fsc),
					'tagline' => array(
						'type' => 'group',
						'label' => __('Site Tagline', 'thesis_classic_r'),
						'fields' => $fsc),
					'menu' => array(
						'type' => 'object',
						'label' => __('Nav Menu', 'thesis_classic_r'),
						'fields' => $nav),
					'sidebar' => array(
						'type' => 'group',
						'label' => __('Sidebar', 'thesis_classic_r'),
						'fields' => $fsc),
					'sidebar_heading' => array(
						'type' => 'group',
						'label' => __('Sidebar Headings', 'thesis_classic_r'),
						'fields' => $fsc))));
	}

	/*
	Skin API method for modifying CSS variables each time CSS is saved.
	Return an array of CSS variables, including units (if necessary), with their new values.
	Any variables not included in the return array will not be modified.
	— http://diythemes.com/thesis/rtfm/api/skin/methods/css_variables/
	*/
	public function css_variables() {
		global $thesis;
		$columns = !empty($this->design['layout']['columns']) && is_numeric($this->design['layout']['columns']) ?
			$this->design['layout']['columns'] : 2;
		$order = !empty($this->design['layout']['order']) && $this->design['layout']['order'] == 'right' ? true : false;
		$px['w_content'] = !empty($this->design['layout']['width-content']) && is_numeric($this->design['layout']['width-content']) ?
			abs($this->design['layout']['width-content']) : 617;
		$px['w_sidebar'] = !empty($this->design['layout']['width-sidebar']) && is_numeric($this->design['layout']['width-sidebar']) ?
			abs($this->design['layout']['width-sidebar']) : 280;
		$px['w_total'] = $px['w_content'] + ($columns == 2 ? $px['w_sidebar'] : 0);
		$vars['font'] = $this->fonts->family($font = !empty($this->design['font']['family']) ? $this->design['font']['family'] : 'georgia');
		$s['content'] = !empty($this->design['font']['size']) ? $this->design['font']['size'] : 16;
		// Determine typographical scale based on primary font size
		$f['content'] = $this->typography->scale($s['content']);
		/*
		The final line height, $h['content'], is calculated in 3 iterations:
		1. Get the optimal line height for the current font and size
		2. Get an adjusted line height using optimal spacing for the current font and size
		3. Adjust the line height a final time with adjusted spacing for the current font and size
		Both the line height, $h['content'], and layout spacing, $x['content'], are calculated below:
		*/
		$x['content'] = $this->typography->space($h['content'] = $this->typography->height($s['content'], ($w['content'] = $px['w_content'] - ($adjust = round(2 * $this->typography->height($s['content'], $px['w_content'] - ($first = round(2 * $this->typography->height($s['content'], false, $font), 0)) - 1, $font), 0)) - 1), $font));
		// Determine sidebar font, size, typographical scale, and spacing
		$sidebar_font = !empty($this->design['sidebar']['font']) ? $this->design['sidebar']['font'] : $font;
		$s['sidebar'] = !empty($this->design['sidebar']['font-size']) && is_numeric($this->design['sidebar']['font-size']) ?
			$this->design['sidebar']['font-size'] : $f['content']['f6'];
		$f['sidebar'] = $this->typography->scale($s['sidebar']);
		$x['sidebar'] = $this->typography->space($h['sidebar'] = $this->typography->height($s['sidebar'], ($w['sidebar'] = $px['w_sidebar'] - 2 * $x['content']['x1']), $sidebar_font));
		// Set up an array containing numerical values that require a unit for CSS output
		$px['f_text'] = $f['content']['f5'];
		$px['f_aux'] = $f['content']['f6'];
		$px['f_subhead'] = $f['content']['f4'];
		$px['h_text'] = round($h['content'], 0);
		$px['h_aux'] = round($this->typography->height($f['content']['f6'], $w['content'], $font), 0);
		foreach ($x['content'] as $dim => $value)
			$px["x_$dim"] = $value;
		foreach ($x['sidebar'] as $dim => $value)
			$px["s_x_$dim"] = $value;
		// Add the 'px' unit to the $px array constructed above
		$vars = is_array($px) ? array_merge($vars, $this->css_tools->unit($px)) : $vars;
		// Use the Colors API to set up proper CSS color references
		foreach (array('text1', 'text2', 'links', 'color1', 'color2', 'color3') as $color)
			$vars[$color] = !empty($this->design[$color]) ? $this->color->css($this->design[$color]) : false;
		// Set up a modification array for individual typograhical overrides
		$elements = array(
			'menu' => array(
				'font-family' => false,
				'font-size' => $f['content']['f6']),
			'title' => array(
				'font-family' => false,
				'font-size' => $f['content']['f1']),
			'tagline' => array(
				'font-family' => false,
				'font-size' => $f['content']['f5'],
				'color' => !empty($vars['text2']) ? $vars['text2'] : false),
			'headline' => array(
				'font-family' => false,
				'font-size' => $f['content']['f3']),
			'subhead' => array(
				'font-family' => false,
				'font-size' => $f['content']['f4']),
			'blockquote' => array(
				'font-family' => false,
				'font-size' => false,
				'color' => !empty($vars['text2']) ? $vars['text2'] : false),
			'code' => array(
				'font-family' => 'consolas',
				'font-size' => false,
				'color' => false),
			'pre' => array(
				'font-family' => 'consolas',
				'font-size' => false,
				'color' => false),
			'sidebar' => array(
				'font-family' => false,
				'font-size' => $f['sidebar']['f5'],
				'color' => false),
			'sidebar_heading' => array(
				'font-family' => false,
				'font-size' => $f['sidebar']['f3'],
				'color' => false));
		// Loop through the modification array to see if any fonts, sizes, or colors need to be overridden
		foreach ($elements as $name => $element) {
			foreach ($element as $p => $def)
				$e[$name][$p] = $p == 'font-family' ?
					(!empty($this->design[$name][$p]) ?
						"$p: ". $this->fonts->family($family[$name] = $this->design[$name][$p]). ';' : (!empty($def) ?
						"$p: ". $this->fonts->family($family[$name] = $def). ';' : false)) : ($p == 'font-size' ?
					(!empty($this->design[$name][$p]) && is_numeric($this->design[$name][$p]) ?
						"$p: ". ($size[$name] = $this->design[$name][$p]). "px;" : (!empty($def) ?
						"$p: ". ($size[$name] = $def). "px;" : false)) : ($p == 'color' ?
					(!empty($this->design[$name][$p]) ?
						"$p: ". $this->color->css($this->design[$name][$p]). ';' : (!empty($def) ?
						"$p: $def;" : false)) : false));
			$e[$name] = array_filter($e[$name]);
		}
		foreach (array_filter($e) as $name => $element)
			$vars[$name] = implode("\n\t", $element);
		// Override content elements
		foreach (array('headline', 'subhead', 'blockquote', 'pre') as $name)
			if (!empty($size[$name]))
				$vars[$name] .= "\n\tline-height: ". ($line[$name] = round($this->typography->height($size[$name], $w['content'], !empty($family[$name]) ? $family[$name] : $font), 0)). "px;";
		// Override sidebar elements
		foreach (array('sidebar', 'sidebar_heading') as $name)
			if (!empty($size[$name]))
				$vars[$name] .= "\n\tline-height: ". round($this->typography->height($size[$name], $w['sidebar'], !empty($family[$name]) ? $family[$name] : $sidebar_font), 0). "px;";
		// Determine multi-use color variables
		foreach (array('title', 'headline', 'subhead') as $name)
			$vars["{$name}_color"] = !empty($this->design[$name]['color']) ?
				$this->color->css($this->design[$name]['color']) : (!empty($vars['text1']) ? $vars['text1'] : false);
		// Set up property-value variables, which, unlike the other variables above, contain more than just a CSS value
		$vars['column1'] =
			"float: ". ($columns == 2 ? ($order ? 'right' : 'left') : 'none'). ";\n\t".
			"border-width: ". ($columns == 2 ? ($order ? '0 0 0 1px' : '0 1px 0 0') : '0'). ";";
		$vars['column2'] =
			"width: ". ($columns == 2 ? '$w_sidebar' : '100%'). ";\n\t".
			"float: ". ($columns == 2 ? ($order ? 'left' : 'right') : 'none'). ';'. ($columns == 1 ?
			"\n\tborder-top: 3px double \$color1;" : '');
		$vars['submenu'] = ($w_submenu = ((!empty($size['menu']) ? $size['menu'] : $px['f_aux']) * 14)). "px";
		$vars['menu'] .= "\n\tline-height: ". round($this->typography->height((!empty($size['menu']) ? $size['menu'] : $px['f_aux']), $w_submenu, !empty($family['menu']) ? $family['menu'] : $font)). "px;";
		$vars['pullquote'] =
			"font-size: ". $f['content']['f3']. "px;\n\t".
			"line-height: ". round($this->typography->height($f['content']['f3'], round(0.45 * $w['content'], 0), !empty($family['blockquote']) ? $family['blockquote'] : $font), 0). "px;";
		$vars['avatar'] =
			"width: ". ($avatar = $line['headline'] + $px['h_aux']). "px;\n\t".
			"height: {$avatar}px;";
		$vars['comment_avatar'] =
			"width: ". (2 * $px['h_text']). "px;\n\t".
			"height: ". (2 * $px['h_text']). "px;";
		foreach (array(2, 3, 4) as $factor)
			if (($bio_size = $factor * $px['h_text']) <= 96)
				$bio = $bio_size;
		$vars['bio_avatar'] =
			"width: {$bio}px;\n\t".
			"height: {$bio}px;";
		return array_filter($vars); // Filter the array to remove any null elements
	}
}