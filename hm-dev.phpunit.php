<?php

if ( ! file_exists( trailingslashit( substr( get_include_path(), 2 ) ) . 'PHPUnit/Autoload.php' ) )
	return;

require_once('PHPUnit/Autoload.php');

class WP_UnitTestCase extends PHPUnit_Framework_TestCase {

    var $url = 'http://example.org/';
	var $plugin_slug = null;

	function _setUp() {
		global $wpdb;
		$wpdb->suppress_errors = false;
		$wpdb->show_errors = true;
		$wpdb->db_connect();
		ini_set('display_errors', 1 );

		$this->start_transaction();
		add_filter( 'gp_get_option_uri', array( $this, 'url_filter') );
		$this->activate_tested_plugin();
    }

	function activate_tested_plugin() {
		if ( !$this->plugin_slug ) {
			return;
		}
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
		if ( file_exists( WP_PLUGIN_DIR . '/' . $this->plugin_slug . '.php' ) )
			activate_plugin( $this->plugin_slug . '.php' );
		elseif ( file_exists( WP_PLUGIN_DIR . '/' . $this->plugin_slug . '/' . $this->plugin_slug . '.php' ) )
			activate_plugin( $this->plugin_slug . '/' . $this->plugin_slug . '.php'  );
		else
			throw new WP_Tests_Exception( "Couldn't find a plugin with slug $this->plugin_slug" );
	}

	function url_filter( $url ) {
		return $this->url;
	}

	function _tearDown() {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' );
		remove_filter( 'gp_get_option_uri', array( $this, 'url_filter') );
	}

	function clean_up_global_scope() {
		wp_cache_flush();
		$_GET = array();
		$_POST = array();
	}

	function start_transaction() {
		global $wpdb;
		$wpdb->query( 'SET autocommit = 0;' );
		$wpdb->query( 'SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE;' );
		$wpdb->query( 'START TRANSACTION;' );
	}

	function force_innodb( $schema ) {
		foreach( $schema as &$sql ) {
			$sql = str_replace( ');', ') TYPE=InnoDB;', $sql );
		}
		return $schema;
	}

	function assertWPError( $actual, $message = '' ) {
		$this->assertTrue( is_wp_error( $actual ), $message );
	}

	function assertEqualFields( $object, $fields ) {
		foreach( $fields as $field_name => $field_value ) {
			if ( $object->$field_name != $field_value ) {
				$this->fail();
			}
		}
	}

	/**
	 * Assert that a zip archive contains the array
	 * of filenames
	 *
	 * @access public
	 * @param string path to zip file
	 * @param array of filenames to check for
	 * @return null
	 */
	function assertArchiveContains( $zip_file, $filepaths, $root = ABSPATH ) {

		$extracted = $this->pclzip_extract_as_string( $zip_file );

		$files = array();

		foreach( $filepaths as $filepath )
			$filenames[] = str_ireplace( $root, '', $filepath );

		foreach( $extracted as $fileInfo )
			$files[] = untrailingslashit( $fileInfo['filename'] );

		foreach( $filenames as $filename )
			$this->assertContains( untrailingslashit( $filename ), $files );

	}

	/**
	 * Assert that a zip archive doesn't contain any of the files
	 * in the array of filenames
	 *
	 * @access public
	 * @param string path to zip file
	 * @param array of filenames to check for
	 * @return null
	 */
	function assertArchiveNotContains( $zip_file, $filenames ) {

		$extracted = $this->pclzip_extract_as_string( $zip_file );

		$files = array();

		foreach( $extracted as $fileInfo )
			$files[] = $fileInfo['filename'];

		foreach( $filenames as $filename )
			$this->assertNotContains( $filename, $files );

	}

	/**
	 * Assert that a zip archive contains the
	 * correct number of files
	 *
	 * @access public
	 * @param string path to zip file
	 * @param int the number of files the archive should contain
	 * @return null
	 */
	function assertArchiveFileCount( $zip_file, $file_count ) {

		$extracted = $this->pclzip_extract_as_string( $zip_file );

		$this->assertEquals( count( array_filter( (array) $extracted ) ), $file_count );

	}

	function assertURL( $url, $message = '' ) {

		$this->assertStringStartsWith( 'http', $url, $message );

	}

	function assertURLReponseCode( $url, $code, $message = '' ) {

		$r = wp_remote_request( $url, array( 'timeout' => 30 ) );
		$r_code = wp_remote_retrieve_response_code( $r );
		$this->assertEquals( $code, $r_code );
	}

	function assertURLContains( $url, $pattern, $message = '' ) {

		$r = wp_remote_request( $url, array( 'timeout' => 30 ) );
		$body = wp_remote_retrieve_body( $r );


		$this->assertContains( $pattern, $body, $message );
	}

	function assertDiscardWhitespace( $expected, $actual ) {
		$this->assertEquals( preg_replace( '/\s*/', '', $expected ), preg_replace( '/\s*/', '', $actual ) );
	}

	function assertImageColorAtPoint( $image_path, $point, $color ) {

	}

	function assertImageAlphaAtPoint( $image_path, $point, $alpha ) {

		$im = imagecreatefrompng( $image_path );
		$rgb = imagecolorat($im, $point[0], $point[1]);

		$colors = imagecolorsforindex($im, $rgb);

		$this->assertEquals( $colors['alpha'], $alpha );
	}

	function checkAtLeastPHPVersion( $version ) {
		if ( version_compare( PHP_VERSION, $version, '<' ) ) {
			$this->markTestSkipped();
		}
	}

	function go_to( $url ) {
		// note: the WP and WP_Query classes like to silently fetch parameters
		// from all over the place (globals, GET, etc), which makes it tricky
		// to run them more than once without very carefully clearing everything
		$_GET = $_POST = array();
		$this->clean_up_global_scope();
		foreach (array('query_string', 'id', 'postdata', 'authordata', 'day', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'pagenow') as $v) {
			if ( isset( $GLOBALS[$v] ) ) unset( $GLOBALS[$v] );
		}
		$parts = parse_url($url);
		if (isset($parts['scheme'])) {
			$req = $parts['path'];
			if (isset($parts['query'])) {
				$req .= '?' . $parts['query'];
				// parse the url query vars into $_GET
				parse_str($parts['query'], $_GET);
			} else {
				$parts['query'] = '';
			}
		}
		else {
			$req = $url;
		}

		$_SERVER['REQUEST_URI'] = $req;
		unset($_SERVER['PATH_INFO']);

		wp_cache_flush();
		unset($GLOBALS['wp_query'], $GLOBALS['wp_the_query']);
		$GLOBALS['wp_the_query'] =& new WP_Query();
		$GLOBALS['wp_query'] =& $GLOBALS['wp_the_query'];
		$GLOBALS['wp'] =& new WP();

		// clean out globals to stop them polluting wp and wp_query
		foreach ($GLOBALS['wp']->public_query_vars as $v) {
			unset($GLOBALS[$v]);
		}
		foreach ($GLOBALS['wp']->private_query_vars as $v) {
			unset($GLOBALS[$v]);
		}

		$GLOBALS['wp']->main($parts['query']);
	}

	private function pclzip_extract_as_string( $zip_file ) {

		require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

 	 	if ( ini_get( 'mbstring.func_overload' ) && function_exists( 'mb_internal_encoding' ) ) {
 	 	    $previous_encoding = mb_internal_encoding();
 	 	 	mb_internal_encoding( 'ISO-8859-1' );
 	 	}

		$archive = new PclZip( $zip_file );

		$extracted = $archive->extract( PCLZIP_OPT_EXTRACT_AS_STRING );

		if ( isset( $previous_encoding ) )
			mb_internal_encoding( $previous_encoding );

		return $extracted ?: array();

	}

}

function hmdev_phpunit_load_all_test_files() {

	if( ! empty( $_POST['tests'] ) ) {
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

		if ( is_dir( get_template_directory() . '/tests/' ) ) {

			foreach ( glob( get_template_directory() . '/tests/*.php' ) as $file )
				include_once( $file );

		}

		if ( is_dir( WPMU_PLUGIN_DIR ) ) {

			foreach ( glob( WPMU_PLUGIN_DIR . '/*/tests/*.php' ) as $file ) {
				include_once( $file );
			}

		}

	}
}

function hmdev_phpunit_get_all_test_cases() {

	$test_classes = array();
	$all_classes = get_declared_classes();
	// only classes that extend WPTestCase and have names that don't start with _ are included
	foreach ($all_classes as $class)
		if ($class{0} != '_' && hmdev_phpunit_test_is_descendent('PHPUnit_Framework_TestCase', $class) && $class != 'WP_UnitTestCase' )
			$test_classes[] = $class;
	return $test_classes;
}

function hmdev_phpunit_test_is_descendent($parent, $class) {

	$ancestor = strtolower(get_parent_class($class));

	while ($ancestor) {
		if ($ancestor == strtolower($parent)) return true;
		$ancestor = strtolower(get_parent_class($ancestor));
	}

	return false;
}