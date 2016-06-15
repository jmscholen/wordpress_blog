<?php
/*
Copyright 2015 DIYthemes, LLC. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.

Version: 1.0
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/
Uses: Thesis object (and more specifically, the active Thesis Skin object)

About this class:
=================
Use this class to calculate dynamic typographical values in your design. You can (and should!) use these values to
determine both layout spacing and typographical characteristics. For the most finely-tuned results possible, be
sure to include a font list that contains mu (character constant) values and, if available, x-height correction
information.
*/
class thesis_skin_typography {
	public $phi = false;			// Golden Ratio value
	public $fonts = array(); 		// array of available fonts in Thesis Font Array Format

	public function __construct() {
		global $thesis;
		if (empty($thesis->environment)) return;
		$this->phi = (1 + sqrt(5)) / 2;
		add_action('init', array($this, 'get_fonts'), 12); // Timing ensures fonts are fully-loaded
	}

	/*
	Attempt to use the Thesis font list for precision tuning
	*/
	public function get_fonts() {
		global $thesis;
		$this->fonts = is_object($thesis) && is_object($thesis->skin) && is_object($thesis->skin->fonts) && !empty($thesis->skin->fonts->list) && is_array($thesis->skin->fonts->list) ?
			$thesis->skin->fonts->list : $this->fonts;
	}

	/*
	Determine the appropriate line height for a given font size and context
	– $size: font size that will serve as the basis for the line height calculation
	– $width: (optional) for precise line height tuning, supply a content width here (use the same units as your font size)
	– $font: (optional) for maximum precision, indicate the font being used (note: $font must match an array key in the $this->fonts list)
	– $cpl: (optional) 75cpl is assumed to be an optimal fulcrum for line height tuning, but you can supply a different value here
	*/
	public function height($size, $width = false, $font = false, $cpl = 75) {
		$a = 1 / (2 * $this->phi);
		/*
		Default tuning scenario: 75cpl at a median-ish mu value of 2.25
		You can use the thesis_cpl filter to override all CPL values in use throughout the Skin (rather than passing each as a parameter)
		*/
		$factor = (is_numeric($filter = apply_filters('thesis_cpl', false)) ?
			$filter : (is_numeric($cpl) ? $cpl : 75)) / (!empty($font) && !empty($this->fonts[$font]) && !empty($this->fonts[$font]['mu']) ? $this->fonts[$font]['mu'] : 2.25);
		// Correction factor of 0.5 is for fonts with large x-heights and/or large vertical footprints
		$correction = !empty($font) && !empty($this->fonts[$font]) && !empty($this->fonts[$font]['x']) ? 0.5 : 0;
		return !empty($size) && is_numeric($size) ?
			$size * (!empty($width) && is_numeric($width) ?
				1 + $a + $a * ($width / ($size * $factor)) :
				$this->phi) + $correction : false;
	}

	/*
	Determine an appropriate content width for a known font size
	– $size: font size that will serve as the basis for the width calculation
	– $font: (optional) for maximum precision, indicate the font being used (note: $font must match an array key in the $this->fonts list)
	– $height: (optional) optimal line height will be used unless you specify a particular line height here
	– $cpl: (optional) 75cpl is assumed to be an optimal width, but you can supply a different value here
	*/
	public function width($size, $font = false, $height = false, $cpl = 75) {
		if (empty($size) || !is_numeric($size)) return false;
		$b = 2 * $this->phi;
		$factor = (is_numeric($filter = apply_filters('thesis_cpl', false)) ?
			$filter : (is_numeric($cpl) ? $cpl : 75)) / (!empty($font) && !empty($this->fonts[$font]) && !empty($this->fonts[$font]['mu']) ? $this->fonts[$font]['mu'] : 2.25);
		$h = (!empty($height) ? $height : $size * $this->phi) + (!empty($font) && !empty($this->fonts[$font]) && !empty($this->fonts[$font]['x']) ? 0.5 : 0);
		return $b * $factor * ($h - $size - ($size / $b));
	}

	/*
	Use your primary font size to determine a typographical scale for your design
	Note: In the return array, index f5 is your primary font size.
	*/
	public function scale($size) {
		return empty($size) || !is_numeric($size) ? false : array(
			'f1' => round($size * pow($this->phi, 2)),			// title
			'f2' => round($size * pow($this->phi, 1.5)),		// headlines 1 (sometimes too large for responsive layouts)
			'f3' => round($size * $this->phi),					// headlines 2 (generally best for use in responsive layouts)
			'f4' => round($size * sqrt($this->phi)),			// sub-headlines
			'f5' => $size,										// primary content text
			'f6' => round($size * (1 / sqrt($this->phi))));		// auxiliary text
	}

	/*
	Use your primary line height to determine the various units of spacing in your design.
	Never use arbitrary padding/margin/spacing values again! Instead, use a spatial scale
	based on the primary line height, and all the spacing in your design will be related.
	*/
	public function space($height) {
		return empty($height) || !is_numeric($height) ? false : array(
			'x1' => ($height = round($height)),					// single
			'x05' => ($half = round($height / 2)),				// half
			'x025' => round($height / 4),						// quarter
			'x15' => $height + $half,							// one-and-a-half
			'x2' => $height * 2,								// double
			'x25' => $height + $height + $half,					// two-and-a-half
			'x3' => $height * 3);								// triple
	}
}