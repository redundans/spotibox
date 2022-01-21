<?php
/**
 * Functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Spotibox
 */

// Run autoload.
require 'vendor/autoload.php';

// REST API.
require_once 'inc/class-spotiboxsession.php';

// Admin pages.
require_once 'inc/class-spotiboxadmin.php';

// REST API.
require_once 'inc/class-spotiboxrest.php';

add_action(
	'wp_enqueue_scripts',
	function() {
		// Google Fonts used by theme.
		wp_enqueue_style(
			'spotibox-google-font',
			'https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;800&display=swap',
			array(),
			'1.0.0',
			'all'
		);

		// Main stylesheet for theme.
		wp_enqueue_style(
			'spotibox-style',
			get_template_directory_uri() . '/dist/style.css',
			array(),
			filemtime( get_template_directory() . '/dist/style.css' ),
			'all'
		);

		// Vue.js.
		wp_enqueue_script(
			'vuejs',
			get_template_directory_uri() . '/dist/vue.js',
			array(),
			filemtime( get_template_directory() . '/dist/vue.js' ),
			true
		);

		// The theme app script.
		wp_enqueue_script(
			'spotibox-script',
			get_template_directory_uri() . '/dist/script.js',
			array( 'vuejs' ),
			filemtime( get_template_directory() . '/dist/script.js' ),
			true
		);
	}
);
