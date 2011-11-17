<?php

class Debug_Bar_PHP extends Debug_Bar_Panel {
	
	var $warnings = array();
	var $notices = array();
	var $ignored = array();
	var $real_error_handler = array();

	function init() {
		
		if ( ! WP_DEBUG )
			return false;

		$this->title( __('Notices / Warnings', 'debug-bar') );
		
		$this->real_error_handler = set_error_handler( array( &$this, 'error_handler' ) );
		
		$this->ignored = array();

		if ( !empty( $_COOKIE['debug-bar-ignored-notices'] ) )
			$this->ignored = json_decode( stripslashes( $_COOKIE['debug-bar-ignored-notices'] ), true );

		
		add_action( 'wp_ajax_ignore_notice', array( &$this, 'ajax' ) );
		
	}

	function prerender() {
		$this->set_visible( count( $this->notices ) || count( $this->warnings ) || count( $this->ignored ) );
	}

	function debug_bar_classes( $classes ) {
		if ( count( $this->warnings ) )
			$classes[] = 'debug-bar-php-warning-summary';
		elseif ( count( $this->notices ) )
			$classes[] = 'debug-bar-php-notice-summary';
		return $classes;
	}

	function error_handler( $type, $message, $file, $line ) {	
		
		
		$_key = md5( $file . ':' . $line . ':' . $message );
		
		if ( in_array( $_key, $this->ignored ) )
			return ! WP_DEBUG_DISPLAY;

		switch ( $type ) {
			case E_WARNING :
			case E_USER_WARNING :
				$this->warnings[$_key] = array( $file.':'.$line, $message );
				break;
			case E_NOTICE :
			case E_USER_NOTICE :
				$this->notices[$_key] = array( $file.':'.$line, $message );
				break;
			case E_STRICT :
				// TODO
				break;
			case E_DEPRECATED :
			case E_USER_DEPRECATED :
				// TODO
				break;
			case 0 :
				// TODO
				break;
		}

		if ( null != $this->real_error_handler && WP_DEBUG_DISPLAY )
			return call_user_func( $this->real_error_handler, $type, $message, $file, $line );
		else
			return ! WP_DEBUG_DISPLAY;
	}

	function render() {
		
		echo "<div id='debug-bar-php'>";
		echo '<h2><span>Total Warnings:</span><strong>' . number_format( count( $this->warnings ) ) . "</strong></h2>\n";
		echo '<h2><span>Total Notices:</span><strong>' . number_format( count( $this->notices ) ) . "</strong></h2>\n";
		echo '<h2><span>Total Ignored:</span><strong>' . number_format( count( $this->ignored ) ) . "</strong></h2>\n";
		
		if ( $this->ignored )
			echo '<a class="reset-ignored" href="">Reset Ignored Notices</a>';
		
		if ( count( $this->warnings ) ) {
			echo '<ol class="debug-bar-php-list">';
			foreach ( $this->warnings as $location_message) {
				list( $location, $message) = $location_message;
				echo "<li class='debug-bar-php-warning'>WARNING: ".str_replace(ABSPATH, '', $location) . ' - ' . strip_tags($message). "</li>";
			}
			echo '</ol>';
		}
		
		if ( count( $this->notices ) ) {
			echo '<ol class="debug-bar-php-list">';
			foreach ( $this->notices as $key => $location_message) {
				list( $location, $message) = $location_message;
				echo "<li data-key='key_$key' class='debug-bar-php-notice'>NOTICE: ".str_replace(ABSPATH, '', $location) . ' - ' . strip_tags($message). "<a href=\"#\">ignore</a></li>";
			}
			echo '</ol>';
		}
		
		echo "</div>";

	}

}

?>