<?php

if ( class_exists( 'WP_CLI_Command' ) ) :

/**
 * Add the import command to WP CLI.
 *
 * Adds easy synching of db and uploads between dev and production
 *
 * @extends WP_CLI_Command
 */
class HMImportCommand extends WP_CLI_Command {

	public static function get_description() {
		return 'Import a DB into the WordPress site.';
	}

	public function db( $command = '', $args = '' ) {

		if ( count( $command ) == 1 && reset( $command ) == 'help' )
			return $this->db_help();

		$defaults = array(
			'host' 		=> defined( 'IMPORT_DB_HOST' ) ? IMPORT_DB_HOST : DB_HOST,
			'user'		=> defined( 'IMPORT_DB_USER' ) ? IMPORT_DB_USER : DB_USER,
			'password' 	=> defined( 'IMPORT_DB_PASSWORD' ) ? IMPORT_DB_PASSWORD : '',
			'name'		=> defined( 'IMPORT_DB_NAME' ) ? IMPORT_DB_NAME : '',
			'port'		=> '3306',
			'ssh_host'	=> defined( 'IMPORT_DB_SSH_HOST' ) ? IMPORT_DB_SSH_HOST : '',
			'ssh_user'	=> defined( 'IMPORT_DB_SSH_USER' ) ? IMPORT_DB_SSH_USER : '',
			'table'		=> ''
		);

		$args = wp_parse_args( $args, $defaults );

		$start_time = time();

		if ( $args['ssh_host'] ) {
			shell_exec( sprintf( "ssh -f -L 3308:%s:%s %s@%s sleep 600 >> logfile", $args['host'], $args['port'], $args['ssh_user'], $args['ssh_host'] ) );
			$args['host'] = '127.0.0.1';
			$args['port'] = '3308';
		}

		WP_CLI::line( 'Importing database from ' . $args['host'] . '...' . ( $args['ssh_host'] ? ' via ssh tunnel: ' . $args['ssh_host'] : '' ) );

		$password = $args['password'] ? '--password=' . escapeshellarg($args['password']) : '';

		// TODO pipe through sed or interconnectIT's search replace script
		if ( defined( 'IMPORT_DB_REMOTE_ABSPATH' ) )
			$sed = " | sed s," . trailingslashit( IMPORT_DB_REMOTE_ABSPATH ) . "," . ABSPATH . ",g";
		else
			$sed = '';

		if ( $args['site'] ) {
			$args['table'] = "wp_{$args['site']}_commentmeta wp_{$args['site']}_comments wp_{$args['site']}_links wp_{$args['site']}_options wp_{$args['site']}_postmeta wp_{$args['site']}_posts wp_{$args['site']}_term_relationships wp_{$args['site']}_term_taxonomy wp_{$args['site']}_terms";
		}

		$exec = sprintf(
			'mysqldump --verbose --host=%s --user=%s %s -P %s %s %s %s | mysql --host=%s --user=%s --password=%s %s',
			$args['host'],
			$args['user'],
			$password,
			$args['port'],
			$args['name'],
			$args['table'],
			$sed,
			DB_HOST,
			DB_USER,
			escapeshellarg(DB_PASSWORD),
			DB_NAME
		);

		WP_CLI::line( 'Running: ' . $exec );

		WP_CLI::launch( $exec );

		WP_CLI::success( sprintf( 'Finished. Took %d seconds', time() - $start_time ) );

		wp_cache_flush();
	}

	public function uploads( $command = array(), $args = array() ) {

		if ( count( $command ) == 1 && reset( $command ) == 'help' )
			return $this->uploads_help();

		$dir = wp_upload_dir();

		$defaults = array(
			'ssh_host' => defined( 'IMPORT_UPLOADS_SSH_HOST' ) ? IMPORT_UPLOADS_SSH_HOST : '',
			'ssh_user' => defined( 'IMPORT_UPLOADS_SSH_USER' ) ? IMPORT_UPLOADS_SSH_USER : '',
			'remote_path' => defined( 'IMPORT_UPLOADS_REMOTE_PATH' ) ? IMPORT_UPLOADS_REMOTE_PATH : '',
			'uploads_dir' => '',
			'local_path' => $dir['basedir']
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['uploads_dir'] ) {

			$args['remote_path'] = $args['remote_path'] ? trailingslashit( $args['remote_path'] ) . trailingslashit( ltrim( $args['uploads_dir'], '/' ) ) : '';
			$args['local_path'] = trailingslashit( $args['local_path'] ) . untrailingslashit( ltrim( $args['uploads_dir'], '/' ) );

		} else {

			$args['remote_path'] = $args['remote_path'] ? trailingslashit( $args['remote_path'] ) : '';
			$args['local_path'] = untrailingslashit( $args['local_path'] );

		}

		if ( empty( $args['remote_path'] ) ) {
			WP_CLI::error( 'You must specify a remote path. Use --remote_path=~/foo/bar' );
			return;
		}

		if ( empty( $args['ssh_host'] ) ) {
			WP_CLI::error( 'You must specify a ssh host. Use --ssh_host=example.com' );
			return;
		}

		if ( empty( $args['ssh_user'] ) ) {
			WP_CLI::error( 'You must specify a ssh user. Use --ssh_user=root' );
			return;
		}

		WP_CLI::line( sprintf( 'Running rsync from %s:%s to %s', $args['ssh_host'], $args['remote_path'], $args['local_path'] ) );

		WP_CLI::launch( sprintf( "rsync -avz -e ssh %s@%s:%s %s --exclude 'cache' --exclude '_wpremote_backups'", $args['ssh_user'], $args['ssh_host'], $args['remote_path'], $args['local_path'] ) );

	}

	/**
	 * Import a specific site from wordpress multisite site
	 * 
	 * @todo add site to wp_blogs if it doesn't exist
	 * @todo support site "nicename" instead of ID. Somehow.
	 */
	public function site( $command, $args ) {

		$site = $command[0];

		WP_CLI::line( 'Importing uploads' );

		$this->uploads( array(), array( 'uploads_dir' => 'sites/' . $site ) );

		$this->db( array(), array( 'site' => $site ) );
	}

	/**
	 * Help function for this command
	 */
	public function help() {
		WP_CLI::line( <<<EOB
wp import db			Import a database from a local or remote server. Supports connecting via SSH. "wp import db help" for details.
wp import uploads		Rsync uploads from a remote server. "wp import uploads help" for details.
wp import site [id]		Import uploads and database from a single site on the remote multisite install
EOB
		);
	}

	private function db_help() {
		WP_CLI::line( <<<EOB
--host					Specify the MySQL hostname. Define IMPORT_DB_HOST for default.
--user					Specify the MySQL user. Define IMPORT_DB_USER for default.
--password				Specify the MySQL password. Define IMPORT_DB_PASSWORD for default.
--port					Specify the MySQL port (default: 3306).
--ssh_host				Optionaly tunnel to a remote host via SSH. You must have pulibkey access to use this option. Define IMPORT_DB_SSH_HOST for default.
--ssh_user				Specify the username for the SSH connection. Define IMPORT_DB_SSH_USER for default.
EOB
		);
	}

	private function uploads_help() {
		WP_CLI::line( <<<EOB
--ssh_host				Specify the SSH server. Define IMPORT_UPLOADS_SSH_HOST for default.
--ssh_user				Specify the username for the SSH connection. Define IMPORT_UPLOADS_SSH_USER for default.
--remote_path			Specify the remote path on the server to the uploads base directory. Define IMPORT_UPLOADS_REMOTE_PATH for default.
--uploads_dir			Optionally set the uploads sub directory to sync (e.g. "2011").
EOB
		);
	}

}

// Register the import command
WP_CLI::addCommand( 'import', 'HMImportCommand' );

endif;