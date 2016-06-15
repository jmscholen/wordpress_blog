/*
Copyright 2012 DIYthemes, LLC. Patent pending. All rights reserved.
DIYthemes, Thesis, and the Thesis Theme are registered trademarks of DIYthemes, LLC.
License: DIYthemes Software License Agreement
License URI: http://diythemes.com/thesis/rtfm/software-license-agreement/
*/
var thesis_terms;
(function(window, $, undefined) {
	thesis_terms = {
		init: function() {
			this.count_field();
			$(document).ajaxComplete(function(e, xhr, settings) {
				if (/add-tag/.test(settings.data))
					thesis_terms.count_field();
			});
		},
		count_field: function() {
			$('.count_field').each(function() {
				var count = $(this).val().length;
				$(this).siblings('.counter').val(count);
				$(this).siblings('label').children('.counter').val(count);
			}).keyup(function() {
				var count = $(this).val().length;
				$(this).siblings('.counter').val(count);
				$(this).siblings('label').children('.counter').val(count);
			});
		}
	};
})(this, jQuery);
jQuery(document).ready(function($) {
	thesis_terms.init();
});