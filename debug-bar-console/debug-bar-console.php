<?php
/*
 Plugin Name: Debug Bar Console
 Plugin URI: http://wordpress.org/extend/plugins/debug-bar-console/
 Description: Adds a PHP/MySQL console to the debug bar. Requires the debug bar plugin.
 Author: koopersmith
 Version: 0.2
 Author URI: http://darylkoop.com/
 */

add_action('debug_bar_panels', 'debug_bar_console_panel');
function debug_bar_console_panel( $panels ) {
	require_once dirname( __FILE__ ) . '/class-debug-bar-console.php';
	$panels[] = new Debug_Bar_Console();
	return $panels;
}

add_action('debug_bar_enqueue_scripts', 'debug_bar_console_scripts');
function debug_bar_console_scripts() {
	$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

	wp_enqueue_style( 'debug-bar-console', plugins_url( "css/debug-bar-console$suffix.css", __FILE__ ), array('debug-bar'), '20110114' );
	wp_enqueue_script( 'debug-bar-console', plugins_url( "js/debug-bar-console$suffix.js", __FILE__ ), array('debug-bar'), '20110114' );
}

?>