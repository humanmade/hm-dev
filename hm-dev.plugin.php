<?php

/*
Plugin Name: HM Dev
Author: Human Made Limited
Version: 1.1
*/

define( 'HM_DEV_SLUG', 'hm-dev' );
define( 'HM_DEV_PATH', realpath( dirname( __FILE__ ) ) . '/' );
define( 'HM_DEV_URL', plugins_url( HM_DEV_SLUG ) );

// Load the debug bar
include_once( HM_DEV_PATH . 'debug-bar/debug-bar.php' );
include_once( HM_DEV_PATH . 'debug-bar-console/debug-bar-console.php' );
include_once( HM_DEV_PATH . 'debug-bar-extender/debug-bar-extender.php' );
include_once( HM_DEV_PATH . 'debug-bar-query-tracer/galahad-query-tracer.php' );

// Re-enqueue the scripts and styles so they work in a sub directory
add_action( 'debug_bar_enqueue_scripts', function() {

	wp_deregister_script( 'debug-bar' );
	wp_deregister_style( 'debug-bar' );
	wp_deregister_script( 'debug-bar-console' );
	wp_deregister_style( 'debug-bar-console' );

	wp_enqueue_style( 'debug-bar', HM_DEV_URL . "/debug-bar/css/debug-bar.css", array(), '20111209' );
	wp_enqueue_script( 'debug-bar', HM_DEV_URL . "/debug-bar/js/debug-bar.js", array( 'jquery' ), '20111209', true );
	wp_enqueue_style( 'debug-bar-console', HM_DEV_URL . "/debug-bar-console/css/debug-bar-console.css", array(), '20111209' );
	wp_enqueue_script( 'debug-bar-console', HM_DEV_URL . "/debug-bar-console/js/debug-bar-console.js", array( 'jquery' ), '20111209', true );

}, 1 );

// Load the unit tests
include_once( HM_DEV_PATH . 'hm-dev.wp-unit.php' );

// Load the time stack
include_once( HM_DEV_PATH . 'hm-dev.time-stack.php' );