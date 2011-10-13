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
	public static function get_description() {
		return 'Run unit tests using WP Unit.';
	}

	public function show( $args = array() ) {
		
		$files = wptest_get_all_test_files(DIR_TESTCASE);
		foreach ($files as $file) {
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
			require_once($file);
		}
		
		if( $test == 'all' || $test == null )
			$this->testCases = wptest_get_all_test_cases();
		else
			$this->testCases = array( $test );
		
		// run the tests and print the results
		
		$result = new PHPUnit_Framework_TestResult; 
		
		list ($result, $printer) = $this->_run_tests($this->testCases, @$opts['t']);	
		
		$this->_output_results( $result );
	
	}
	
	private function _run_tests($classes, $classname='') {
		$suite = new PHPUnit_Framework_TestSuite(); 
		foreach ($classes as $testcase)
		{	
			if (!$classname or strtolower($testcase) == strtolower($classname)) {
				$suite->addTestSuite($testcase);		
			}
		}
		
		#return PHPUnit::run($suite);
		$result = new PHPUnit_Framework_TestResult; 
		
		require_once('PHPUnit/TextUI/ResultPrinter.php');
		
		$printer = new WPUnitCommandResultsPrinter();
		$result->addListener($printer);
		
		return array($suite->run($result), $printer);
	}
	
	private function _output_results( $result ) {
		
		$pass 				= $result->passed();
		$number_of_tests 	= $result->count();
		$detected_failures 	= $result->failureCount();
		$failures			= $result->failures();
		$incompleted_tests	= $result->notImplemented();
		$skipped 			= $result->skipped();
		$number_skipped		= $result->skippedCount();
		$passedKeys 		= array_keys($pass);
		$skippedKeys 		= array_keys($skipped);
		$incompletedKeys 	= array_keys($incompleted_tests);
		
		$tests = array();
		$_tests = array_merge( $passedKeys, $skippedKeys, $incompletedKeys );
		
		foreach ( $_tests as $_test )
			$tests[ reset( explode( '::', $_test ) ) ][] = array( 'method' => end( explode( '::', $_test ) ), 'status' => 'OK' );
		
		foreach ( $failures as $failure ) {
					
			$failedTest = $failure->failedTest();
						
			if ($failedTest instanceof PHPUnit_Framework_SelfDescribing) 
		    	$_test = $failedTest->toString();
			
			else 
		    	$_test = get_class($failedTest);
		    
		    $tests[ reset( explode( '::', $_test ) ) ][] = array( 'method' => end( explode( '::', $_test ) ), 'status' => 'Failed', 'message' => $failure->getExceptionAsString() );

		}
		
		$failed_count = 0;

		foreach ( $tests as $test_case => $test_case_tests ) {
			foreach ( $test_case_tests as $test_case_test ) {
			
				switch ( $test_case_test['status'] ) : 
					case 'Failed' :
						$failed_count++;
						break;
				endswitch;
			
			}
		
		}
		
		WP_CLI::line( '' );
		
		if( $failed_count )
			WP_CLI::error( 'Ran ' . $number_of_tests . ' tests. ' . $failed_count . ' Failed.' );
		else
			WP_CLI::success( 'Ran ' . $number_of_tests . ' tests. ' . $failed_count . ' Failed.' );
	}
	
}
endif;
class WPUnitCommandResultsPrinter extends PHPUnit_TextUI_ResultPrinter implements PHPUnit_Framework_TestListener {

	var $failed_tests;
	
    public function printResult(PHPUnit_Framework_TestResult $result) {}
    
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
    	$name = preg_replace( '(^.*::(.*?)$)', '\\1', $suite->getName() );
    	
    	WP_CLI::line( '' );
    	WP_CLI::line( $name );
    }
	
    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
    	
    	$this->failed_tests[$test->getName()] = $e;
    	
    }
	
    public function endTest(PHPUnit_Framework_Test $test, $time) {
       	
       	$name = preg_replace( '(^(.*)::(.*?)$)', '\\1', $test->getName() );
       	
		if( !empty( $this->failed_tests[$test->getName()] ) )
       		WP_CLI::error( '  '.$name . ' ' . $this->failed_tests[$test->getName()]->toString() );
       	
       	else
       		WP_CLI::success( $name );
    }

}