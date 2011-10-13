<?php

//Get all plugins
if( !empty( $_POST['tests'] ) ) {
	foreach( (array) $_POST['tests'] as $test_file )
		if( strpos( $test_file, '.php' ) ) {
			include_once( WP_PLUGIN_DIR . '/' . $test_file );
		} else {
			$files = wptest_get_all_test_files( WP_PLUGIN_DIR . '/' . $test_file );

			foreach ($files as $file) {
				require_once($file);
			}
		
		}
} else {

	$plugins = get_plugins();
	
	foreach( $plugins as $plugin_path => $plugin ) {
		if( is_plugin_active( $plugin_path ) ) {
			foreach( get_plugin_files( $plugin_path ) as $file )
				if( strpos( $file, '/tests/' ) && end( explode( '.', $file) ) == 'php' )
					include_once( WP_PLUGIN_DIR . '/' . $file );
		}
	
	}
}