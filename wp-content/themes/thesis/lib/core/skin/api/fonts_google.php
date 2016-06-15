<?php
/*
Copyright 2015 DIYthemes, LLC. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.

Version: 1.0.1
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/
*/
class thesis_skin_fonts_google {
	private $options = array();			// (array) current Skin Design options
	private $selected = array();		// (array) Google Fonts currently selected in Skin Design options
	public $fonts = array();			// (array) available Google Fonts, including any that have been added

	public function __construct($options) {
		global $thesis;
		$this->options = is_array($options) ? $options : $this->options;
		$this->get_fonts();
		add_filter('thesis_fonts', array($this, 'add_fonts'));				// filter for adding fonts to Thesis dropdowns
		add_action('thesis_hook_head', array($this, 'webfont_loader'));		// front-end filter for anything that needs to be in the <head>
		if ($thesis->environment != 'admin') return;
		$this->editor_styles();
	}

	public function get_fonts() {
		$this->fonts = $this->fonts();
		$this->find_fonts($this->options);	// See if any Google Fonts are selected in Skin Design options
	}

	/*
	This method is really just a recursive value search for multi-dimensional arrays...
	As such, it should probably become a general API candidate.
	*/
	public function find_fonts($options) {
		if (is_array($options))
			foreach ($options as $item)
				if (is_array($item))
					$this->find_fonts($item);
				elseif (array_key_exists($item, $this->fonts))
					$this->selected[] = $item;
	}

	/*
	– $fonts: the master array of fonts registered for use within Thesis
	*/
	public function add_fonts($fonts) {
		return is_array($add_fonts = $this->fonts) ? (is_array($fonts) ? array_merge($fonts, $add_fonts) : $add_fonts) : $fonts;
	}

	public function webfont_loader() {
		$families = $this->verify($this->selected, $this->fonts, true);
		if (empty($families)) return;
		echo
			"<script>",
			"WebFontConfig = {",
			"google: { families: [", implode(', ', $families), "] },",
			"};",
			"(function() {",
			"var wf = document.createElement('script');",
			"wf.src = ('https:' == document.location.protocol ? 'https' : 'http') + '://cdnjs.cloudflare.com/ajax/libs/webfont/1.6.21/webfontloader.js';",
			"wf.type = 'text/javascript';",
			"wf.async = 'true';",
			"var s = document.getElementsByTagName('script')[0];",
			"s.parentNode.insertBefore(wf, s);",
			"})();",
			"</script>\n";
	}

	/*
	Method for adding Google Fonts to the WP Post Editor
	*/
	public function editor_styles() {
		$families = $this->verify($this->selected, $this->fonts);
		if (empty($families)) return;
		add_editor_style('//fonts.googleapis.com/css?family='. str_replace(',', '%2C', str_replace(' ', '+', implode('|', $families))));
	}

	/*
	Operational method for verifying Google Fonts selected by the user and then returning the appropriate family references
	for use. If the user has selected a font, we want to make sure it's a Google Font before serving the JS and affecting
	performance in this manner.
	– $options: an array of options that *could* contain font selections
	– $fonts: an array of fonts following the format established in the fonts() method below
	– $js: if the resulting font families are to be served inside JS, set this to true
	*/
	public function verify($options, $fonts, $js = false) {
		$verified = $families = array();
		if (is_array($options) && is_array($fonts))
			foreach ($options as $font)
				if (!empty($fonts[$font]) && !empty($fonts[$font]['styles']))
					$verified[$font] = $fonts[$font];
		if (empty($verified)) return false;
		foreach ($verified as $name => $font)
			if (!empty($font['styles'])) 
				$families[] = $js ? "'{$font['styles']}'" : $font['styles'];
		return $families;
	}

	function fonts() {
		/*
		Each of the following Google Fonts contains 400, 400 italic, and 700 (bold) styles, making it suitable for use in primary content.
		If a font has a large x-height that requires a line-height correction, it will contain 'x' => true
		Also, the following is an array filter for adding any other Google Fonts to this Skin.
		To construct your fonts array, follow this format, where $name is the proper name of the Google Font you wish to add:
		$fonts['$name'] = array(
			'styles' => '300,300italic,900',		// (optional) include styles here; 400,400italic,700 is the default
			'type' => $type,						// (optional) where $type = 'serif' or 'sans-serif'
			'mu' => $mu,							// (optional) include if you know the numerical mu value (character constant) for this font
			'x' => true/false);						// (optional) include and set to true if the font has a large x-height and requires a correction
		*/
		$fonts = array();
		$primary_fonts = apply_filters('thesis_google_fonts', array(
			'Alegreya' => array(
				'type' => 'serif',
				'mu' => 2.47),
			'Alegreya SC' => array(
				'type' => 'serif',
				'mu' => 2.16,
				'x' => true),
			'Alegreya Sans' => array(
				'type' => 'sans-serif',
				'mu' => 2.65),
			'Alegreya Sans SC' => array(
				'type' => 'sans-serif',
				'mu' => 2.36,
				'x' => true),
			'Almendra' => array(
				'type' =>'serif',
				'mu' => 2.49),
			'Amaranth' => array(
				'type' => 'sans-serif',
				'mu' => 2.36),
			'Amiri' => array(
				'type' => 'serif',
				'mu' => 2.49),
			'Anonymous Pro' => array(
				'type' => 'sans-serif',
				'mu' => 1.83,
				'x' => true),
			'Archivo Narrow' => array(
				'type' => 'sans-serif',
				'mu' => 2.75),
			'Arimo' => array(
				'type' => 'sans-serif',
				'mu' => 2.26),
			'Arvo' => array(
				'type' => 'serif',
				'mu' => 2.06),
			'Asap' => array(
				'type' => 'sans-serif',
				'mu' => 2.28),
			'Averia Libre' => array(
				'type' => 'sans-serif',
				'mu' => 2.25),
			'Averia Sans Libre' => array(
				'type' => 'sans-serif',
				'mu' => 2.29),
			'Averia Serif Libre' => array(
				'type' => 'serif',
				'mu' => 2.2),
			'Bitter' => array(
				'type' => 'serif',
				'mu' => 2.08,
				'x' => true),
			'Cabin' => array(
				'type' => 'sans-serif',
				'mu' => 2.41),
			'Cambay' => array(
				'type' => 'sans-serif',
				'mu' => 2.28),
			'Cantarell' => array(
				'type' => 'sans-serif',
				'mu' => 2.16),
			'Cardo' => array(
				'type' => 'serif',
				'mu' => 2.37),
			'Caudex' => array(
				'type' => 'serif',
				'mu' => 2.23),
			'Cousine' => array(
				'type' => 'sans-serif',
				'mu' => 1.67,
				'x' => true),
			'Crimson Text' => array(
				'type' => 'serif',
				'mu' => 2.57),
			'Cuprum' => array(
				'type' => 'sans-serif',
				'mu' => 2.63),
			'Droid Serif' => array(
				'type' => 'serif',
				'mu' => 2.1,
				'x' => true),
			'Economica' => array(
				'type' => 'sans-serif',
				'mu' => 3.28),
			'Exo' => array(
				'type' => 'sans-serif',
				'mu' => 2.24),
			'Exo 2' => array(
				'type' => 'sans-serif',
				'mu' => 2.22),
			'Expletus Sans' => array(
				'type' => 'sans-serif',
				'mu' => 2.19),
			'Fira Sans' => array(
				'type' => 'sans-serif',
				'mu' => 2.2),
			'Gentium Basic' => array(
				'type' => 'serif',
				'mu' => 2.44),
			'Gentium Book Basic' => array(
				'type' => 'serif',
				'mu' => 2.38),
			'Gudea' => array(
				'type' => 'sans-serif',
				'mu' => 2.37),
			'Istok Web' => array(
				'type' => 'sans-serif',
				'mu' => 2.23),
			'Josefin Sans' => array(
				'type' => 'sans-serif',
				'mu' => 2.51),
			'Josefin Slab' => array(
				'type' => 'serif',
				'mu' => 2.38),
			'Judson' => array(
				'type' => 'serif',
				'mu' => 2.37),
			'Karla' => array(
				'type' => 'sans-serif',
				'mu' => 2.2),
			'Lato' => array(
				'type' => 'sans-serif',
				'mu' => 2.33),
			'Lekton' => array(
				'type' => 'sans-serif',
				'mu' => 2),
			'Libre Baskerville' => array(
				'type' => 'serif',
				'mu' => 1.96,
				'x' => true),
			'Lobster Two' => array(
				'type' => 'serif',
				'mu' => 2.78),
			'Lora' => array(
				'type' => 'serif',
				'mu' => 2.16),
			'Marvel' => array(
				'type' => 'sans-serif',
				'mu' => 2.92),
			'Merriweather' => array(
				'type' => 'serif',
				'mu' => 2,
				'x' => true),
			'Merriweather Sans' => array(
				'type' => 'sans-serif',
				'mu' => 2.05,
				'x' => true),
			'Neuton' => array(
				'type' => 'serif',
				'mu' => 2.64),
			'Nobile' => array(
				'type' => 'sans-serif',
				'mu' => 2.1,
				'x' => true),
			'Noticia Text' => array(
				'type' => 'serif',
				'mu' => 2.14,
				'x' => true),
			'Noto Sans' => array(
				'type' => 'sans-serif',
				'mu' => 2.14,
				'x' => true),
			'Noto Serif' => array(
				'type' => 'serif',
				'mu' => 2.1,
				'x' => true),
			'Old Standard TT' => array(
				'type' => 'serif',
				'mu' => 2.3,
				'x' => true),
			'Open Sans' => array(
				'type' => 'sans-serif',
				'mu' => 2.17,
				'x' => true),
			'Overlock' => array(
				'type' => 'sans-serif',
				'mu' => 2.5),
			'Philosopher' => array(
				'type' => 'sans-serif',
				'mu' => 2.36),
			'Playfair Display' => array(
				'type' => 'serif',
				'mu' => 2.24,
				'x' => true),
			'Playfair Display SC' => array(
				'type' => 'serif',
				'mu' => 1.83,
				'x' => true),
			'PT Sans' => array(
				'type' => 'sans-serif',
				'mu' => 2.35),
			'PT Serif' => array(
				'type' => 'serif',
				'mu' => 2.25),
			'Puritan' => array(
				'type' => 'sans-serif',
				'mu' => 2.38),
			'Quantico' => array(
				'type' => 'sans-serif',
				'mu' => 2.12),
			'Quattrocento Sans' => array(
				'type' => 'sans-serif',
				'mu' => 2.33,
				'x' => true),
			'Raleway' => array(
				'type' => 'sans-serif',
                'mu' => 2.17,
				'x' => true),
			'Rambla' => array(
				'type' => 'sans-serif',
				'mu' => 2.47,
				'x' => true),
			'Roboto' => array(
				'type' => 'sans-serif',
				'mu' => 2.24,
				'x' => true),
			'Roboto Condensed' => array(
				'type' => 'sans-serif',
				'mu' => 2.6,
				'x' => true),
			'Roboto Mono' => array(
				'type' => 'sans-serif',
				'mu' => 1.66,
				'x' => true),
			'Roboto Slab' => array(
				'type' => 'serif',
				'mu' => 2.12,
				'x' => true),
			'Rosario' => array(
				'type' => 'sans-serif',
				'mu' => 2.41),
			'Rubik' => array(
				'type' => 'sans-serif',
				'mu' => 2.15),
			'Scada' => array(
				'type' => 'sans-serif',
				'mu' => 2.3),
			'Share' => array(
				'type' => 'sans-serif',
				'mu' => 2.5),
			'Source Sans Pro' => array(
				'type' => 'sans-serif',
				'mu' => 2.42),
			'Tinos' => array(
				'type' => 'serif',
				'mu' => 2.48),
			'Titillium Web' => array(
				'type' => 'sans-serif',
				'mu' => 2.4),
			'Trochut' => array(
				'type' => 'sans-serif',
				'mu' => 2.86),
			'Ubuntu' => array(
				'type' => 'sans-serif',
				'mu' => 2.22,
				'x' => true),
			'Ubuntu Mono' => array(
				'type' => 'sans-serif',
				'mu' => 2),
			'Volkhov' => array(
				'type' => 'serif',
				'mu' => 2.09,
				'x' => true),
			'Vollkorn' => array(
				'type' => 'serif',
				'mu' => 2.32)));
		if (is_array($primary_fonts))
			foreach ($primary_fonts as $name => $font)
				$fonts[$name] = array_filter(array(
					'name' => "$name (G)",
					'family' => "\"$name\"". (!empty($font['type']) ? ", {$font['type']}" : ''),
					'styles' => "$name:". (!empty($font['styles']) ? $font['styles'] : "400,400italic,700"),
					'mu' => isset($font['mu']) && is_numeric($font['mu']) ? $font['mu'] : false,
					'x' => !empty($font['x']) ? $font['x'] : false));
		return $fonts;
	}
}