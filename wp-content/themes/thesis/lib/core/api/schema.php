<?php
/*
Copyright 2013 DIYthemes, LLC. Patent pending. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/
*/
class thesis_schema {
	public $schema = array(
		'Article',
		'BlogPosting',
		'CreativeWork',
		'Event',
		'NewsArticle',
		'Product',
		'Recipe',
		'Review',
		'WebPage');
	public $types = array();

	public function __construct() {
		add_action('init', array($this, 'init'), 12);
	}

	public function init() {
		$this->schema = is_array($schema = apply_filters('thesis_schema', $this->schema)) ? $schema : array();
		foreach ($this->schema as $type)
			if (!empty($type))
				$this->types[strtolower($type)] = "http://schema.org/$type";
	}

	public function select() {
		$options = array();
		foreach ($this->schema as $type)
			if (!empty($type))
				$options[strtolower($type)] = $type;
		ksort($options);
		return array(
			'type' => 'select',
			'label' => __('Schema', 'thesis'),
			'tooltip' => sprintf(__('Enrich your pages by adding a <a href="%s" target="_blank">markup schema</a> that is universally recognized by search engines.', 'thesis'), 'http://schema.org/'),
			'options' => $options);
	}
}