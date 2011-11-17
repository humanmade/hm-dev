<?php

/*
Plugin Name: HM Dev
Author: Human Made Limited
Version: 1.1
*/

define( 'HM_DEV_SLUG', 'hm-dev' );
define( 'HM_DEV_PATH', dirname( __FILE__ ) . '/' );
define( 'HM_DEV_URL', str_replace( ABSPATH, site_url( '/' ), HM_DEV_PATH ) );

// Load the debug bar
include_once( HM_DEV_PATH . 'debug-bar/debug-bar.php' );
include_once( HM_DEV_PATH . 'debug-bar-console/debug-bar-console.php' );

// Load the unit tests
include_once( HM_DEV_PATH . 'hm-dev.wp-unit.php' );

// Load the time stack
include_once( HM_DEV_PATH . 'hm-dev.time-stack.php' );