<?php
/*
Copyright 2015 DIYthemes, LLC. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.

Version: 1.0
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/

About this class:
=================
Use the methods in this class to enhance the color capabilities of your design. You can use the methods below to take
your hexadecimal (hex) or RGB color values, perform some magic, and then return new colors that you'll be able to use
in a dynamic way.

TODO: Add a complement() method that takes an RGB or hex input and returns a complement of the specified type (same type if no type is supplied)
*/
class thesis_skin_color {
	public $conflict = array(		// 3- and 6-letter "natural-name" colors that could conflict with 3- and 6-character hex color inputs
		'bisque',
		'indigo',
		'maroon',
		'orange',
		'orchid',
		'purple',
		'red',
		'salmon',
		'sienna',
		'silver',
		'tan',
		'tomato',
		'violet',
		'yellow');

	/*
	Convert a hex or RGB color value into a hex or RGB color syntax suitable for output in CSS
	— $color: hex or RGB input
	*/
	public function css($color) {
		return is_array($color) && isset($color['r']) && isset($color['g']) && isset($color['b']) ?
			"rgb({$color['r']}, {$color['g']}, {$color['b']})" : ((strlen($color) == 3 || strlen($color) == 6) && !in_array($color, $this->conflict) ?
			"#$color" : $color);
	}

	/*
	Acquire the HSL representation of a color
	— $color: hex or RGB input
	*/
	public function hsl($color) {
		if (empty($color) || !is_array($color = is_array($color) ? $color : $this->hex_to_rgb($color)) || (!isset($color['r']) || !isset($color['g']) || !isset($color['b']))) return false;
		$rgb = array(
			'r' => $color['r'] / 255,
			'g' => $color['g'] / 255,
			'b' => $color['b'] / 255);
		$l = (($max = max($rgb['r'], $rgb['g'], $rgb['b'])) + ($min = min($rgb['r'], $rgb['g'], $rgb['b']))) / 2;
		$chroma = $max - $min;
		if ($chroma == 0)
			return array('h' => 0, 's' => 0, 'l' => 100 * $l);
		$s = $chroma / (1 - abs(2 * $l - 1));
		$h = 60 * ($min == $rgb['r'] ?
			3 - (($rgb['g'] - $rgb['b']) / $chroma) : ($min == $rgb['g'] ?
			5 - (($rgb['b'] - $rgb['r']) / $chroma) :
			1 - (($rgb['r'] - $rgb['g']) / $chroma)));
		return array('h' => round($h, 2), 's' => round(100 * $s, 2), 'l' => round(100 * $l, 2));
	}

	/*
	Convert a color from hex to RGB
	— $hex: hex color input
	*/
	public function hex_to_rgb($hex) {
		if (empty($hex) || is_array($hex) || (strlen($hex) != 6 && strlen($hex) != 3) || in_array($hex, $this->conflict)) return false;
		$rgb = array();
		if (strlen($hex) == 6) {
			$rgb['r'] = hexdec($hex[0]. $hex[1]);
			$rgb['g'] = hexdec($hex[2]. $hex[3]);
			$rgb['b'] = hexdec($hex[4]. $hex[5]);
		}
		elseif (strlen($hex) == 3) {
			$rgb['r'] = hexdec($hex[0]. $hex[0]);
			$rgb['g'] = hexdec($hex[1]. $hex[1]);
			$rgb['b'] = hexdec($hex[2]. $hex[2]);
		}
		return $rgb;
	}

	/*
	Convert a color from RGB to hex
	— $rgb: RGB color input of the form array('r' => , 'g' => , 'b' => )
	*/
	public function rgb_to_hex($rgb) {
		if (!is_array($rgb) || (!isset($rgb['r']) || !isset($rgb['g']) || !isset($rgb['b']))) return false;
		return sprintf('%02s', dechex($rgb['r'])). sprintf('%02s', dechex($rgb['g'])). sprintf('%02s', dechex($rgb['b']));
	}

	/*
	Convert a color from HSL to RGB
	— $hsl: HSL color input of the form array('h' => , 's' => , 'l' => )
	*/
	public function hsl_to_rgb($hsl) {
		if (empty($hsl) || !is_array($hsl) || (!isset($hsl['h']) || !isset($hsl['s']) || !isset($hsl['l']))) return false;
		$h = $hsl['h'];
		$s = $hsl['s'] / 100;
		$l = $hsl['l'] / 100;
		$chroma = $s * (1 - abs(2 * $l - 1));
		$x = $chroma * (1 - abs(fmod($h / 60, 2) - 1));
		$rgb = $h < 60 ?
			array('r' => $chroma, 'g' => $x, 'b' => 0) : ($h < 120 ?
			array('r' => $x, 'g' => $chroma, 'b' => 0) : ($h < 180 ?
			array('r' => 0, 'g' => $chroma, 'b' => $x) : ($h < 240 ?
			array('r' => 0, 'g' => $x, 'b' => $chroma) : ($h < 300 ?
			array('r' => $x, 'g' => 0, 'b' => $chroma) :
			array('r' => $chroma, 'g' => 0, 'b' => $x)))));
		$m = $l - ($chroma / 2);
		return array(
			'r' => round(255 * ($rgb['r'] + $m)),
			'g' => round(255 * ($rgb['g'] + $m)),
			'b' => round(255 * ($rgb['b'] + $m)));
	}

	/*
	Determine the HSL complement of a color according to the conditions set forth in the parameters.
	Supplying only an $hsl parameter will yield exactly one 180-degree color complement
	— $hsl: HSL color input of the form array('h' => , 's' => , 'l' => )
	— $h: integer value indicating the angular distance you wish to travel to acquire complementary colors (0–360)
	— $s: integer value indicating the desired saturation difference (0–100)
	— $l: integer value indicated the desired lightness difference (0–100)
	*/
	public function hsl_complement($hsl, $h = 180, $s = 0, $l = 0, $smax = 100, $smin = 0, $lmax = 100, $lmin = 0) {
		if (empty($hsl) || !is_array($hsl) || (!isset($hsl['h']) || !isset($hsl['s']) || !isset($hsl['l']))) return false;
		$smax = $smax > 100 ? 100 : ($smax < 0 ? 0 : $smax);
		$smin = $smin < 0 ? 0 : ($smin > 100 ? 100 : $smin);
		$lmax = $lmax > 100 ? 100 : ($lmax < 0 ? 0 : $lmax);
		$lmin = $lmin < 0 ? 0 : ($lmin > 100 ? 100 : $lmin);
		return array(
			'h' => ($hx = $hsl['h'] + $h) > 360 || $hx < 0 ? $hsl['h'] - $h : $hx,
			's' => ($sx = $hsl['s'] + $s) > $smax ? $smax : ($sx < $smin ? $smin : $sx),
			'l' => ($lx = $hsl['l'] + $l) > $lmax ? $lmax : ($lx < $lmin ? $lmin : $lx));
	}

	/*
	Set an HSL color value directly by passing any parameter(s)
	— $hsl: input HSL color of the form array('h' => , 's' => , 'l' => )
	— $h: desired hue (0–360)
	— $s: desired saturation (0–100)
	— $l: desired lightness (0–100)
	*/
	public function hsl_set($hsl, $h = false, $s = false, $l = false) {
		if (empty($hsl) || !is_array($hsl) || (!isset($hsl['h']) || !isset($hsl['s']) || !isset($hsl['l']))) return false;
		return (array(
			'h' => !empty($h) && $h >= 0 && $h <= 360 ? $h : $hsl['h'],
			's' => !empty($s) && $s >= 0 && $s <= 100 ? $s : $hsl['s'],
			'l' => !empty($l) && $l >= 0 && $l <= 100 ? $l : $hsl['l']));
	}

	/*
	Lighten or darken a color by a specified amount
	— $color: hex or RGB input
	— $lightness: the desired lightness change (positive values = lighter, negative values = darker)
		— absolute mode: the new lightness will be whatever you specify here (must be positive)
		— relative mode: the new lightness will be the original lightness plus or minus the value specified here
		— spread mode: the new lightness will be a percentage lighter or darker than the input color (50 = 50% lighter, -50 = 50% darker)
	— $args: array(
		— $mode: absolute, relative, or spread (absolute is the default)
		— $return_rgb: returns hex by default or RGB value if $return_rgb is true
	*/
	public function lightness($color, $lightness, $args = array()) {
		if (empty($color) || !is_array($hsl = $this->hsl($color)) || (!is_numeric($lightness) || abs($lightness) > 100)) return false;
		extract($args = is_array($args) ? $args : array());
		$mode = !empty($mode) && ($mode == 'relative' || $mode == 'spread') ? $mode : 'absolute';
		$return_rgb = !empty($return_rgb) ? true : false;
		if ($mode == 'absolute')
			$l = $lightness >= 0 ? $lightness : 0;
		elseif ($mode == 'relative') {
			$l = $hsl['l'] + $lightness;
			$l = $l > 100 ? 100 : ($l < 0 ? 0 : $l);
		}
		elseif ($mode == 'spread') {
			$pct = abs($lightness) > 1 ? $lightness / 100 : $lightness;
			$spread = $pct < 0 ? $hsl['l'] : 100 - $hsl['l'];
			$l = $hsl['l'] + $spread * $pct;
		}
		$rgb = $this->hsl_to_rgb(array('h' => $hsl['h'], 's' => $hsl['s'], 'l' => $l));
		return $return_rgb ? $rgb : $this->rgb_to_hex($rgb);
	}

	/*
	Returns an array of hex and RGB values of the grayscale equivalent of any color. Includes 3 operating modes:
		1. luminosity: weighted average of RGB components corresponding to perceived intensity
		2. average: average of RGB components (considered less accurate than luminosity method)
		3. saturation: crank the HSL saturation component to zero
	— $color: hex or RGB input
	*/
	public function gray($color, $mode = 'luminosity') {			
		if (!($hsl = $this->hsl($color))) return;
		$rgb = array();
		if ($mode == 'luminosity' || $mode == 'average') {
			$c = $this->hsl_to_rgb($hsl);
			$x = round($mode == 'luminosity' ?
				0.2988 * $c['r'] + 0.5870 * $c['g'] + 0.1140 * $c['b'] :
				array_sum($c) / count($c));
			$rgb['r'] = $rgb['g'] = $rgb['b'] = $x;
		}
		elseif ($mode == 'saturation')
			$rgb = $this->hsl_to_rgb(array_merge($hsl, array('s' => 0)));
		return array(
			'hex' => $this->rgb_to_hex($rgb),
			'rgb' => $rgb);
	}
}