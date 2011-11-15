<?php

class Debug_Bar_Console extends Debug_Bar_Panel {
	function init() {
		$this->title( 'Console' );
		add_action( 'wp_ajax_debug_bar_console', array( &$this, 'ajax' ) );
	}

	function prerender() {
		$this->set_visible( true );
	}

	function render() { ?>
		<form id="debug-bar-console">
		<input id="debug-bar-console-iframe-css" type="hidden"
			value="<?php echo plugins_url( 'css/iframe.dev.css', __FILE__ ); ?>" />
		<?php wp_nonce_field( 'debug_bar_console', '_wpnonce_debug_bar_console' ); ?>
		<div id="debug-bar-console-wrap" class="debug-bar-console">
			<textarea id="debug-bar-console-input" class="debug-bar-console"></textarea>
		</div>
		<div id="debug-bar-console-output" class="debug-bar-console">
			<iframe></iframe>
		</div>
		<a href="#" id="debug-bar-console-submit"><?php _e('Run'); ?></a>
		</form>
		<?php
	}

	function ajax() {
		global $wpdb;

		check_admin_referer( 'debug_bar_console', 'nonce' );

		if ( ! is_super_admin() )
			die();

		$data = stripslashes( $_POST['data'] );

		if ( preg_match( "/^\s*(SELECT|UPDATE|ALTER|DELETE|CREATE|INSERT)\s/i", $data ) ) {
			$data = explode( ";\n", $data );
			foreach ( $data as $query ) {
				$this->print_mysql_table( $wpdb->get_results( $query, ARRAY_A ), $query );
			}
			die();
		}

		eval( $data );
		die();
	}

	function print_mysql_table( $data, $query='' ) {
		if ( empty( $data ) )
			return;

		$keys = array_keys( $data[0] );

		echo '<table class="mysql" cellpadding="0"><thead>';

		if ( ! empty( $query ) )
			echo "<tr class='query'><td colspan='" . count($keys) . "'>$query</td></tr>";

		echo '<tr>';
		foreach ( $keys as $key ) {
			echo "<th class='$key'>$key</th>";
		}
		echo '</tr></thead><tbody>';

		foreach ( $data as $row ) {
			echo '<tr>';
			foreach ( $row as $key => $value ) {
				echo "<td class='$key'>" . esc_html($value) . "</td>";
			}
			echo '</tr>';
		}

		echo '</tbody></table></div>';
	}
}

?>