<?php

/*
Plugin Name: HM Dev
Description: Code like a poet
Author: Human Made Limited
Version: 1.3
*/

define( 'HM_DEV_PATH', plugin_dir_path( __FILE__ ) );
define( 'HM_DEV_URL', plugin_dir_url( __FILE__ ) );

require_once( HM_DEV_PATH . 'hm-dev.debug.php' );
require_once( HM_DEV_PATH . 'hm-dev.mail.php' );

// Load the unit tests
//require_once( HM_DEV_PATH . 'wp-unit/wp-unit.plugin.php' );
if ( ! class_exists( 'WP_UnitTestCase' ) ) {
	require_once( HM_DEV_PATH . 'hm-dev.phpunit.php' );
	require_once( HM_DEV_PATH . 'hm-dev.wp-cli.test.php' );
}

// Load the time stack
if ( file_exists( HM_DEV_PATH . 'timestack/time-stack.php' ) && ! class_exists( 'HM_Time_Stack' ) )
	require_once( HM_DEV_PATH . 'timestack/time-stack.php' );

// Load the import, export commands
require_once( HM_DEV_PATH . 'hm-dev.wp-cli.import.php' );

// Hook in and prefix the <title /> and admin_bar site name with DEV ?
add_filter( 'wp_title', $dev_title = function( $title ) {

	$mark = 'DEV &bull; ';

	// Make sure it isn't added twice
	return  $mark . str_ireplace( $mark, '', $title );

} );

add_filter( 'admin_title', $dev_title );

add_filter( 'admin_bar_menu', function() use ( $dev_title ) {

	global $wp_admin_bar;

	$wp_admin_bar->add_node( array(
		'id'	=> 'site-name',
		'title'	=> $dev_title( $wp_admin_bar->get_node( 'site-name' )->title )
	) );

}, 31 );

add_filter( 'admin_head', 'hm_dev_colorize' );
add_filter( 'wp_head', 'hm_dev_colorize' );
function hm_dev_colorize() {
?>
<style>

	#wpadminbar {
		border-top: 5px solid rgba(193, 39, 45, 1);
	}

	#wp-admin-bar-site-name > a {
		background-color: rgba(193, 39, 45, 1);
		color: #f1f1f1;
	}

</style>
<?php
}