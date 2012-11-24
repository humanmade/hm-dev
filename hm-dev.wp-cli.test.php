<?php

if ( class_exists( 'WP_CLI_Command' ) ) :

/**
 * WPUnitCommand class.
 *
 * @extends WP_CLI_Command
 */
class WPUnitCommand extends WP_CLI_Command {

	var $testCases;
	var $printer;

	protected $default_subcommand = 'run';

	public static function get_description() {
		return 'Run unit tests using WP Unit.';
	}

	public function show( $args = array() ) {

		if ( ! $this->test_for_phpunit() )
			return;

		hmdev_phpunit_load_all_test_files();

		$tests = hmdev_phpunit_get_all_test_cases();

		WP_CLI::line( '' );

		foreach( $tests as $t )
			WP_CLI::line( $t );

		WP_CLI::line( '' );

	}

	public function run( $args = array(), $assoc_args = array() ) {

		if ( ! $this->test_for_phpunit() )
			return;

		$test = isset( $args[0] ) ? $args[0] : null;

		hmdev_phpunit_load_all_test_files();

		if ( empty( $test ) ) {
			$this->testCases = hmdev_phpunit_get_all_test_cases();

		} else {

			if ( strpos( $test, '*' ) ) {

				foreach ( hmdev_phpunit_get_all_test_cases() as $_test )
					if ( preg_match( '/' . str_replace( '*', '([.]*?)', $test ) . '/', $_test ) )
						$this->testCases[] = $_test;

			} else {

				$this->testCases = array( $test );

			}
		}

		// Run the tests and print the results
		$result = new PHPUnit_Framework_TestResult;

		list ( $result, $printer ) = $this->_run_tests( $this->testCases );

		$this->_output_results( $result );

	}

	private function _run_tests( $classes, $classname = '' ) {

		$suite = new PHPUnit_Framework_TestSuite();

		// Turn off BackUpGlobal until https://github.com/sebastianbergmann/phpunit/issues/451 is fixed
		$suite->setBackupGlobals( false );

		foreach ( $classes as $testcase )
			if ( ! $classname or strtolower( $testcase ) === strtolower( $classname ) )
				$suite->addTestSuite( $testcase );

		$result = new PHPUnit_Framework_TestResult;

		require_once( 'PHPUnit/TextUI/ResultPrinter.php' );

		$this->printer = new WPUnitCommandResultsPrinter();
		$result->addListener( $this->printer );

		return array( $suite->run( $result ), $this->printer );
	}


	private function _output_results( $result ) {

		WP_CLI::line( '' );

		WP_CLI::line( sprintf( 'Ran %d tests. %%R%d%%n Failed, %%Y%d%%n Skipped, %%G%d%%n Passed', count( $this->printer->failed_tests ) + count( $this->printer->skipped_tests ) + count( $this->printer->passed_tests ),count( $this->printer->failed_tests ), count( $this->printer->skipped_tests ), count( $this->printer->passed_tests ) ) );

		WP_CLI::line( '' );

	}

	private function test_for_phpunit() {

		if ( ! file_exists( trailingslashit( substr( get_include_path(), 2 ) ) . 'PHPUnit/Autoload.php' ) ) {

			WP_CLI::line( '%RPHPUnit not found%n, you need to install PHPUnit to use the test command, see https://github.com/humanmade/hm-dev' );

			WP_CLI::line( 'Attempting to auto install PHPUnit...' );

			WP_CLI::launch( 'pear config-set auto_discover 1' );
			WP_CLI::launch( 'sudo pear install pear.phpunit.de/PHPUnit' );

			if ( file_exists( trailingslashit( substr( get_include_path(), 2 ) ) . 'PHPUnit/Autoload.php' ) )
				WP_CLI::line( '%GPHPUnit was auto installed%n, You\'ll need to run the command again.' );

			return false;

		}

		return true;

	}

}
if ( class_exists( 'PHPUnit_TextUI_ResultPrinter' ) ) :
class WPUnitCommandResultsPrinter extends PHPUnit_TextUI_ResultPrinter implements PHPUnit_Framework_TestListener {

	var $failed_tests;
	var $skipped_tests;
	var $passed_tests;
	var $current_test_suite;

    public function printResult( PHPUnit_Framework_TestResult $result ) {}

    public function startTestSuite( PHPUnit_Framework_TestSuite $suite ) {

    	$name = preg_replace( '(^.*::(.*?)$)', '\\1', $suite->getName() );

    	$this->current_test_suite = $name;

    	WP_CLI::line( $name );

    }

    public function addFailure( PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time ) {

    	$name = strpos( $test->getName(), '::' ) ? $test->getName() : $this->current_test_suite . '::' . $test->getName();
    	$this->failed_tests[$name] = $e->toString();

    }

	public function addError( PHPUnit_Framework_Test $test, Exception $e, $time ) {

        $name = strpos( $test->getName(), '::' ) ? $test->getName() : $this->current_test_suite . '::' . $test->getName();
        $this->failed_tests[$name] = 'Script Error: ' . $e->getMessage() . ' [file: ' . $e->getFile() . ' line: ' . $e->getLine() . ' backtrace: ' . $e->getTraceAsString() . ']';

    }

	public function addSkippedTest( PHPUnit_Framework_Test $test, Exception $e, $time ) {

		$name = strpos( $test->getName(), '::' ) ? $test->getName() : $this->current_test_suite . '::' . $test->getName();
        $this->skipped_tests[$name] = 'Skipped message: ' . $e->getMessage();


	}

	public function addIncompleteTest( PHPUnit_Framework_Test $test, Exception $e, $time ) {

		$name = strpos( $test->getName(), '::' ) ? $test->getName() : $this->current_test_suite . '::' . $test->getName();
        $this->skipped_tests[$name] = 'Skipped message: ' . $e->getMessage();

	}

    public function endTest( PHPUnit_Framework_Test $test, $time ) {

       	$name = preg_replace( '(^(.*)::(.*?)$)', '\\1', $test->getName() );
       	$full_name = strpos( $test->getName(), '::' ) ? $test->getName() : $this->current_test_suite . '::' . $test->getName();

		if ( isset( $this->failed_tests[$full_name] ) ) {
       		$this->print_failed( $name . ' ' . $this->failed_tests[$full_name] );

       	} elseif( isset( $this->skipped_tests[$full_name] ) ) {
       		$this->print_skipped( $name . ' ' . $this->skipped_tests[$full_name] );

       	} else {
       		$this->print_passed( $name );
       		$this->passed_tests[$name] = $name;

       	}

    }

    private function print_passed( $message ) {

    	WP_CLI::line( '%GPassed: %n' . $message );

    }

    private function print_failed( $message ) {

    	WP_CLI::line( '%RFailed: %n' . $message );

    }

    private function print_skipped( $message ) {

	    WP_CLI::line( '%YSkipped: %n' . $message );

    }

}
endif;

// Add the command to the wp-cli
WP_CLI::addCommand( 'test', 'WPUnitCommand' );

endif;