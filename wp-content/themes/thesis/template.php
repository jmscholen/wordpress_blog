<?php
/*
	Template Name: Default
*/

/*

The existence of this file is a cruel joke. If you've ever saved a _page_ and chosen a custom template for that page, and then if you switch to a theme that doesn't include any WordPress-style custom templates, you will never be able to save custom post meta data on that page again until you de-select the custom template. (Remember, these are WordPress custom templates and not Thesis custom templates.)

For you nerds out there, the root of this problem lies in wp-includes/post.php, which includes custom template logic for pages that is flawed. Specifically, the wp_insert_post() function returns 0 when it should simply finish out its normal process by calling the save_post hook.

We are attempting to get WordPress to listen to reason here and change this faulty logic.

(It's worth noting that we now have 2 useless files in Thesis to trick WordPress' faulty logic: comments.php and template.php. Le sigh.)

*/