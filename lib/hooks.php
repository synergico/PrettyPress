<?php
/**
 * @package PrettyPress
 */

/*
The MIT License (MIT)

Copyright (c) 2013 evasivesoftware.com

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/


//Defend our stuff!
if ( ! defined('ABSPATH') ) { exit; }

if ( $prettypress_config['enabled'] == "enabled" ) {
	//Register our hooks.

	//The CSS.
	add_action( 'admin_enqueue_scripts', 'prettypress_css_hook' );

	//The meta box
	add_action( 'add_meta_boxes', 'prettypress_meta_box' );

	//The page hooks.
	add_action( 'edit_form_after_editor', 'prettypress_edit_hook' );
	add_action( 'edit_page_form', 'prettypress_edit_hook' );

	//The live page hooks
	add_filter( 'the_content', 'prettypress_thecontent' );
	add_filter( 'the_title', 'prettypress_thetitle' );

	//Autosave for posts that don't have a post ID yet.
	add_filter('redirect_post_location', 'prettypress_autosave');


} else {
	//PrettyPress is disabled.
	//Go outside and play!
}



function prettypress_autosave( $location ) {

	global $post;

	//Make sure we are saving.
	if (! empty($_POST['save']) ) {

		//Make sure it's a draft.
		if ( $_POST['save'] == "Save Draft" ) {

			if (! empty($_POST['prettypress_active']) ) {

				//We know for sure that this post save was triggered by PrettyPress.
				//We are safe to assume that a PrettyPress auto-launch has been triggered.
				$location .= "&prettypress_active=1";

			}

		}

	}

	return $location;

}

function prettypress_edit_hook() {

	//Include the primary edit page on "edit" entries.
	require_once PLUGINPATH . '/view/edit.php';

}

function prettypress_css_hook() {

	global $prettypress_config;

	//Register and queue the stylesheet.
	wp_register_style( 'prettypress_css', PRETTYPRESS_BASE_URL . "/assets/css/prettypress.css", false );
	wp_enqueue_style( 'prettypress_css' );

	if ( $prettypress_config['legacy'] == "enabled" ) {
		wp_register_style( 'prettypress_css_legacy', PRETTYPRESS_BASE_URL . "/assets/css/prettypress-legacy.css", false );
		wp_enqueue_style( 'prettypress_css_legacy' );
	}


}

function prettypress_meta_box() {

	// start fresh for an array of post types we want to register PP on
	$registerOn = array();

	// Add posts and pages by default
	$registerOn[] = 'post';
	$registerOn[] = 'page';

	// We also want to automatically add all custom post types
	$args = array(
		'public'   => true,
		'_builtin' => false
	);

	$publicCPTs = get_post_types( $args, 'names', 'and' );

	if( is_array( $publicCPTs && !empty( $publicCPTs ) ) )
		foreach( $publicCPTs as $key => $cptName )
			$registerOn[] = $cptName;

	// Run it through a filter so we can amend this elsehwere
	$registerOn = apply_filters( 'prettypress_post_types_to_show_metabox', $registerOn );

	// Also have a filter for the location and priority so we're not forcing this
	$location = apply_filters( 'prettypress_metabox_location', 'side' );
	$priority = apply_filters( 'prettypress_metabox_priority', 'high' );

	if( !is_array( $registerOn ) || empty( $registerOn ) )
		return;

	foreach( $registerOn as $key => $cptName )
		add_meta_box( 'prettypress_meta_hwnd', __( 'PrettyPress', 'prfx-textdomain' ), 'prettypress_meta_hwnd_callback', $cptName, $location, $priority );

}

function prettypress_meta_hwnd_callback( $post ) {

	//Include the metabox page.
	require_once PLUGINPATH . '/view/metabox.php';

}

function prettypress_thecontent( $content ) {

	//We're yet to find a circumstance where we shouldn't automatically
	//filter the_content (except for guest viewers).

	if ( is_user_logged_in() ) {
		return '<span data-rel="content">' . $content . '</span>';
	} else {
		return $content;
	}
}

function prettypress_thetitle( $title ) {

	global $id;

	//There's no point potentially breaking W3C/HTML for a viewing only user.
	//Only edit post entries, not menus.
	//By requiring $id, we can be sure a call has not occured from wp_nav_menu to the_title
	//Otherwise, all the menu title will update with the current post composition.
	//This should be refined further
	//See http://codex.wordpress.org/Conditional_Tags
	//Fix this

	if ( is_user_logged_in() && $id ) {
		if ( is_admin() ) {
			global $pagenow;
			if ( $pagenow != 'edit.php' && $pagenow != "upload.php" ) {
				return '<span data-rel="title">' . $title . '</span>';
			} else {
				return $title;
			}
		} else {
			return '<span data-rel="title">' . $title . '</span>';
		}
	} else {
		return $title;
	}

}

?>
