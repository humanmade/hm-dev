<?php

/*
Plugin Name: HM Dev
Description: Code like a poet
Author: Human Made Limited
Version: 1.2
*/

define( 'HM_DEV_SLUG', 'hm-dev' );
define( 'HM_DEV_PATH', plugin_dir_path( __FILE__ ) );
define( 'HM_DEV_URL', plugin_dir_url( __FILE__ ) );

include_once( HM_DEV_PATH . 'hm-dev.debug.php' );
include_once( HM_DEV_PATH . 'hm-dev.mail.php' );

// Load the unit tests
include_once( HM_DEV_PATH . 'hm-dev.wp-unit.php' );

// Load the time stack
if ( file_exists( HM_DEV_PATH . 'timestack/time-stack.php' ) && ! class_exists( 'HM_Time_Stack' ) )
	include_once( HM_DEV_PATH . 'timestack/time-stack.php' );

// Load the import, export commands
include_once( HM_DEV_PATH . 'hm-dev.wp-cli.import.php' );
