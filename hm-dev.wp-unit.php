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

	public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {

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
endif;