<?php

require_once( dirname( __FILE__ ) . '/wp-unit/WPUnit.php' );

/*
 * Author: Joe Hoyle
 * URL: joehoyle.co.uk
 * Contact: joe@humanmade.co.uk
 */

// Add the command to the wp-cli, only if the plugin is loaded
if ( function_exists( 'wp_unit_add_pages' ) && class_exists( 'WP_CLI' ) ) {
	WP_CLI::addCommand( 'test', 'WPUnitCommand' );
}

/**
 * WP Unit
 *
 * @package wp-cli
 * @subpackage commands/community
 * @author Joe Hoyle
 */

if( class_exists( 'WP_CLI_Command' ) ) :
class WPUnitCommand extends WP_CLI_Command {

	var $testCases;
	var $printer;

	protected $default_subcommand = 'run';

	public static function get_description() {
		return 'Run unit tests using WP Unit.';
	}

	public function show( $args = array() ) {

		$files = wptest_get_all_test_files(DIR_TESTCASE);

		foreach ($files as $file) {

			if ( in_array( basename( $file ), array( 'MyTest.php', 'MyTestSecond.php' ) ) )
				continue;

			require_once($file);

		}

		$tests = wptest_get_all_test_cases();

		foreach( $tests as $t )
			WP_CLI::line( $t );

		WP_CLI::line( '' );

	}

	public function run( $args = array(), $assoc_args = array() ) {

		$test = isset( $args[0] ) ? $args[0] : null;

		$files = wptest_get_all_test_files(DIR_TESTCASE);

		foreach ($files as $file) {

			if ( in_array( basename( $file ), array( 'MyTest.php', 'MyTestSecond.php' ) ) )
				continue;

			require_once($file);

		}

		if( $test == null ) {
			$this->testCases = wptest_get_all_test_cases();
		} else {

			if( strpos( $test, '*' ) ) {

				foreach( wptest_get_all_test_cases() as $_test )
					if( preg_match( '/' . str_replace( '*', '([.]*?)', $test ) . '/', $_test ) )
						$this->testCases[] = $_test;

			} else {

				$this->testCases = array( $test );
			}
		}

		// run the tests and print the results

		$result = new PHPUnit_Framework_TestResult;

		list ($result, $printer) = $this->_run_tests($this->testCases, @$opts['t']);

		$this->_output_results( $result );

	}

	private function _run_tests($classes, $classname='') {
		$suite = new PHPUnit_Framework_TestSuite();

		// Turn off BackUpGlobal until https://github.com/sebastianbergmann/phpunit/issues/451 is fixed
		$suite->setBackupGlobals( false );

		foreach ($classes as $testcase)
		{
			if (!$classname or strtolower($testcase) == strtolower($classname)) {
				$suite->addTestSuite($testcase);
			}
		}

		#return PHPUnit::run($suite);
		$result = new PHPUnit_Framework_TestResult;

		require_once('PHPUnit/TextUI/ResultPrinter.php');

		$this->printer = new WPUnitCommandResultsPrinter();
		$result->addListener($this->printer);

		return array($suite->run($result), $this->printer);
	}

	private function _output_results( $result ) {

		WP_CLI::line( '' );

			WP_CLI::line( sprintf( 'Ran %d tests. %s Failed, %d Skipped, %d Passed',
				count( $this->printer->failed_tests ) + count( $this->printer->skipped_tests ) + count( $this->printer->passed_tests ),
				count( $this->printer->failed_tests ),
				count( $this->printer->skipped_tests ),
				count( $this->printer->passed_tests ) ) );
	}

}

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
		);

		$args = wp_parse_args( $args, $defaults );

		$start_time = time();

		if ( $args['ssh_host'] ) {

			if ( ! in_array( $args['host'], array( '127.0.0.1', 'localhost' ) ) ) {
				WP_CLI::error( 'Connecting to MySQL over SSH currently is only supported 127.0.0.1 for the remote server.' );
				return;
			}

			shell_exec( sprintf( "ssh -f -L 3308:127.0.0.1:%s %s@%s sleep 600 >> logfile", $args['port'], $args['ssh_user'], $args['ssh_host'] ) );
			$args['host'] = '127.0.0.1';
			$args['port'] = '3308';
		}

		WP_CLI::line( 'Importing database from ' . $args['host'] . '...' . ( $args['ssh_host'] ? ' via ssh tunnel: ' . $args['ssh_host'] : '' ) );

		$password = $args['password'] ? '--password=' . $args['password'] : '';

		// TODO pipe through sed

		$exec = sprintf( 'mysqldump --verbose --host=%s --user=%s %s -P %s %s | mysql --host=%s --user=%s --password=%s %s',
			$args['host'], $args['user'], $password, $args['port'], $args['name'], DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );

		exec( $exec );

		WP_CLI::success( sprintf( 'Finished. Took %d seconds', time() - $start_time ) );

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

		$exec = sprintf( "rsync -avz -e ssh %s@%s:%s %s --exclude 'cache' --exclude '_wpremote_backups'", $args['ssh_user'], $args['ssh_host'], $args['remote_path'], $args['local_path'] );

		WP_CLI::line( sprintf( 'Running rsync from %s:%s to %s', $args['ssh_host'], $args['remote_path'], $args['local_path'] ) );

		$res = exec( $exec );

		WP_CLI::line( $res );
	}

	/**
	 * Help function for this command
	 */
	public function help() {
		WP_CLI::line( <<<EOB
wp import db            Import a database from a local or remote server. Supports connecting via SSH. "wp import db help" for details.
wp import uploads		Rsync uploads from a remote server. "wp import uploads help" for details.
EOB
		);
	}

	private function db_help() {
		WP_CLI::line( <<<EOB
--host             			Specify the MySQL hostname. Define IMPORT_DB_HOST for default.
--user					Specify the MySQL user. Define IMPORT_DB_USER for default.
--password				Specify the MySQL password. Define IMPORT_DB_PASSWORD for default.
--port					Specify the MySQL port (default: 3306).
--ssh_host				Optionaly tunnel to a remote host via SSH. You must have pulibkey access to use this option. Define IMPORT_DB_SSH_HOST for default.
					This options is currently only supported with --host=127.0.0.1.
--ssh_user				Specify the username for the SSH connection. Define IMPORT_DB_SSH_USER for default.
EOB
		);
	}

	private function uploads_help() {
		WP_CLI::line( <<<EOB
--ssh_host			Specify the SSH server. Define IMPORT_UPLOADS_SSH_HOST for default.
--ssh_user			Specify the username for the SSH connection. Define IMPORT_UPLOADS_SSH_USER for default.
--remote_path			Specify the remote path on the server to the uploads base directory. Define IMPORT_UPLOADS_REMOTE_PATH for default.
--uploads_dir			Optionally set the uploads sub directory to sync (e.g. "2011").
EOB
		);
	}

}
WP_CLI::addCommand( 'import', 'HMImportCommand' );
endif;

class WPUnitCommandResultsPrinter extends PHPUnit_TextUI_ResultPrinter implements PHPUnit_Framework_TestListener {

	var $failed_tests;
	var $skipped_tests;
	var $passed_tests;
	var $current_test_suite;

    public function printResult(PHPUnit_Framework_TestResult $result) {}

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
    	$name = preg_replace( '(^.*::(.*?)$)', '\\1', $suite->getName() );

    	$this->current_test_suite = $name;
    	WP_CLI::line( '' );
    	WP_CLI::line( $name );
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {

    	$name = strpos( $test->getName(), '::' ) ? $test->getName() : $this->current_test_suite . '::' . $test->getName();
    	$this->failed_tests[$name] = $e->toString();

    }

	public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {

        $name = strpos( $test->getName(), '::' ) ? $test->getName() : $this->current_test_suite . '::' . $test->getName();
        $this->failed_tests[$name] = 'Script Error: ' . $e->getMessage() . ' [file: ' . $e->getFile() . ' line: ' . $e->getLine() . ' backtrace: ' . $e->getTraceAsString() . ']';

    }

	public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {

		$name = strpos( $test->getName(), '::' ) ? $test->getName() : $this->current_test_suite . '::' . $test->getName();
        $this->skipped_tests[$name] = 'Skipped message: ' . $e->getMessage();


	}

    public function endTest(PHPUnit_Framework_Test $test, $time) {

       	$name = preg_replace( '(^(.*)::(.*?)$)', '\\1', $test->getName() );
       	$full_name = strpos( $test->getName(), '::' ) ? $test->getName() : $this->current_test_suite . '::' . $test->getName();

		if( isset( $this->failed_tests[$full_name] ) ) {
       		$this->print_failed( $name . ' ' . $this->failed_tests[$full_name] );

       	}
       	elseif( isset( $this->skipped_tests[$full_name] ) ) {
       		$this->print_skipped( $name . ' ' . $this->skipped_tests[$full_name] );
       	}
       	else {
       		$this->print_passed( $name );
       		$this->passed_tests[$name] = $name;
       	}
    }

    private function print_passed( $message ) {

    	\cli\line( '%GPassed: %n' . $message );

    }

    private function print_failed( $message ) {

    	\cli\line( '%RFailed: %n' . $message );

    }

    private function print_skipped( $message ) {

    	\cli\line( '%YSkipped: %n' . $message );

    }

}