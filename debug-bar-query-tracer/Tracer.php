<?php

/**
 * Galahad Query Tracer
 * @author Chris Morrell <http://cmorrell.com/>
 */
class Galahad_Query_Tracer
{
	/**
	 * Allows faux-singleton access
	 * @var Galahad_Query_Tracer
	 */
	protected static $_instance = null;
	
	/**
	 * Stored Backtrace Data
	 * @var array
	 */
	protected $_data = array();
	
	/**
	 * Whether data has been parsed yet
	 * @var bool
	 */
	protected $_parsed = false;

	/**
	 * Faux-Singleton Accessor
	 * Use this method to get a consistent instance of the class
	 * 
	 * @return Galahad_Query_Tracer
	 */
	public static function instance()
	{
		if (!self::$_instance) {
			self::$_instance = new self;
		}
	
		return self::$_instance;
	}
	
	/**
	 * Constructor
	 * Adds Wordpress filters necessary
	 */
	public function __construct()
	{
		add_filter('query', array($this, 'traceFilter'));
	}
	
	/**
	 * Filters wpdb::query
	 * This filter stores all queries and their backtraces for later use
	 * 
	 * @param string $query
	 * @return string
	 */
	public function traceFilter($query)
	{
		$trace = debug_backtrace();
		array_splice($trace, 0, 3); // Get rid of the tracer's fingerprint (and wpdb::query)
		$this->_data[] = array('query' => $query, 'backtrace' => $trace);
		return $query;
	}
	
	/**
	 * Get and optionally parse the data
	 * 
	 * @return array
	 */
	public function getData()
	{
		// Parse if necessary
		if (!$this->_parsed) {
			$pluginsPath = WP_CONTENT_DIR . '/plugins/'; // Have to do this due to symlink issue
			$rawData = $this->_data;
			$this->_data = array();
			
			// Gather data about existing plugins
			$rootData = array();
			foreach (get_plugins() as $filename => $data) {
				list($root) = explode('/', $filename, 2);
				$rootData[$root] = array_change_key_case($data);
			}
			
			// Parse each query's backtrace
			foreach ($rawData as $query) {
				$functionChain = array();
				foreach ($query['backtrace'] as $call) {
					// Add to function chain
					$functionChain[] = (isset($call['class']) ? "{$call['class']}::" : '') . $call['function'];
					
					// We've got a plugin
					if (false !== strpos($call['file'], $pluginsPath)) {
						list($root) = explode('/', plugin_basename($call['file']), 2);
						$file = str_replace($pluginsPath, '', $call['file']);
						
						// Make sure the array is set up
						if (!isset($this->_data[$root])) {
							$this->_data[$root] = $rootData[$root];
							$this->_data[$root]['backtrace'] = array();
						}
						
						// Make sure the backtrace for this file is set up
						if (!isset($this->_data[$root]['backtrace'][$file])) {
							$this->_data[$root]['backtrace'][$file] = array();
						} 
						
						// Save parsed data
						$this->_data[$root]['backtrace'][$file][] = array(
							'line' => $call['line'],
							'query' => $query['query'],
							'function_chain' => array_reverse($functionChain),
						);
					}
				}
			}
			
			$this->_parsed = true;
			usort($this->_data, array($this, '_sortByName'));
		}
		
		return $this->_data;
	}
	
	/**
	 * Faux-private function for sorting data
	 * 
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	public function _sortByName($a, $b)
	{
		return strcmp($a['name'], $b['name']);
	}
}