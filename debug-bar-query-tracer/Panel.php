<?php

/**
 * Galahad Query Tracer Panel
 * @author Chris Morrell <http://cmorrell.com/>
 */
class Galahad_Query_Tracer_Panel extends Debug_Bar_Panel
{
	/**
	 * Initialize Panel
	 */
	public function init() {
		$this->title(__('Query Tracer', 'galahad-query-tracer'));
	}

	/**
	 * Determine whether panel should render
	 */
	public function prerender() {
		$this->set_visible(is_admin());
	}

	/**
	 * Render Panel
	 */
	public function render() {
		?>
		
		<style type="text/css">
			#galahad-tracer h2 { float: none; border: none; text-align: left; padding: 0; }
			#galahad-tracer h3 { margin-left: 15px; font-size: 1.7em; font-weight: bold; }
			#galahad-tracer table { max-width: 100%; margin-bottom: 1em; margin-left: 30px; }
			#galahad-tracer table, #galahad-tracer th, #galahad-tracer td { border: 1px solid #ccc; border-collapse: collapse; }
			#galahad-tracer th { background: #eee; }
			#galahad-tracer th, #galahad-tracer td { padding: 5px; }
			#galahad-tracer .functions { white-space: nowrap; overflow: auto; }
		</style>
		
		<div id="galahad-tracer">
			<?php 
			
			$data = Galahad_Query_Tracer::instance()->getData();
			if (!empty($data)) {
				$this->_renderData(); 
			} else {
				_e('No queries caused by plugins.');
			}
			
			?>
		</div>
		
		<?php
	}
	
	/**
	 * Render tracer's data
	 * 
	 * @param array $data
	 */
	protected function _renderData($data)
	{
		foreach ($data as $pluginData) {
			echo "<h2>{$pluginData['name']}</h2>";
			foreach ($pluginData['backtrace'] as $filename => $data) {
				$filename = htmlspecialchars($filename);
				printf('<h3>%s</h3>
					  <table>
					  <tr>
					  	<th>%s</th>
					  	<th>%s</th>
					  </tr>', htmlspecialchars($filename), __('Line', 'galahad-query-tracer'), __('Query &amp; Function Chain', 'galahad-query-tracer'));
				foreach ($data as $query) {
					$query['query'] = htmlspecialchars($query['query']);
					$functionChain = implode(' &#8594; ', $query['function_chain']);
					echo "<tr>
							<td align=\"center\" valign=\"center\" rowspan=\"2\">{$query['line']}</td>
							<td>{$query['query']}</td>
						  </tr>
						  <tr>
						  	<td>$functionChain</td>
						  </tr>";
				}
				echo '</table>';
			}
		}
	}
}