<?php

class HM_Time_Stack {

	private static $instance;
	private $stack;
	private $start_time;
	
	public static function instance() {
	
		if ( !isset( self::$instance ) )
			self::$instance = new HM_Time_Stack();
		
		return self::$instance;
	}
	
	function __construct() {
		
		global $hm_time_stack_start;
		
		if( !empty( $hm_time_stack_start ) )
			$this->start_time = $hm_time_stack_start;
		else
			$this->start_time = hm_time_stack_time();
		
		$this->stack = new HM_Time_Stack_Operation( 'wp' );
		
		$this->setup_hooks();
	}
	
	public function start_operation( $id, $label = '' ) {
	
		$operation = new HM_Time_Stack_Operation( $id, $label );
		$this->stack->add_operation( $operation );
	
	}
	
	public function end_operation( $id ) {
		
		$this->stack->end_operation( $this->stack->get_child_operation_by_id( $id ) );	
	}
	
	public function add_event( $id, $label = '' ) {
		
		$event = new HM_Time_Stack_Event( $id, $label );
		$this->stack->add_event( $event );
	}
	
	public function printStack() {
	
		?>
		<style>
			.time-stack { border: 1px solid #ccc; background: #fff; list-style: none; font-size: 11px; background: #333; padding: 0; }
			.time-stack > li { color: #fff; padding: 0 !important; }
			.time-stack > li > ul { padding: 0; color: #333; }
			.time-stack ul { margin: 0; padding: 0 5px; list-style: none;  background: #fff; }
			.time-stack ul li { padding: 3px; border-bottom: 1px solid #ddd; }
			.time-stack .duration { float: right; color: #555; }
			.time-stack .operation { padding: 5px 3px; background: rgba(192, 163, 67, .3); }
			.time-stack .event { color: #666; }
		</style>
		<ul class="time-stack">
			<?php $this->stack->_print(); ?>
		</ul>
		<?php
	
	}
	
	private function setup_hooks() {
	
		add_action( 'init', function() {
			HM_Time_Stack::instance()->add_event( 'init' );
		}, 1 );
		
		add_action( 'parse_query', function( $wp_query ) {
			
			$query = is_string( $wp_query->query ) ? $wp_query->query : json_encode( $wp_query->query );
			HM_Time_Stack::instance()->start_operation( 'wp_query::' . spl_object_hash( $wp_query ), 'WP_Query - ' . $query );
			
		}, 1 );
		
		add_action( 'the_posts', function( $posts, $wp_query ) {
		
			HM_Time_Stack::instance()->end_operation( 'wp_query::' . spl_object_hash( $wp_query ) );
			return $posts;
		}, 99, 2 );
		
		add_action( 'wp_footer', function() {
		
			HM_Time_Stack::instance()->end_operation( 'wp' );
			
			HM_Time_Stack::instance()->printStack();
		
		}, 99 );
		
		add_action( 'template_redirect', function() {
		
			HM_Time_Stack::instance()->add_event( 'template_redirect' );
		
		}, 1 );
		
		add_action( 'wp_head', function() {
		
			HM_Time_Stack::instance()->add_event( 'wp_head' );
		
		}, 1 );
		
		add_action( 'loop_start', function( $wp_query ) {
		
			$query = is_string( $wp_query->query ) ? $wp_query->query : json_encode( $wp_query->query );
			HM_Time_Stack::instance()->add_event( 'the_loop::' . spl_object_hash( $wp_query ), 'Loop Start - ' . $query );
		
		}, 1 );
		
		add_action( 'loop_end', function( $wp_query ) {
		
			$query = is_string( $wp_query->query ) ? $wp_query->query : json_encode( $wp_query->query );
			HM_Time_Stack::instance()->add_event( 'the_loop::' . spl_object_hash( $wp_query ), 'Loop End - ' . $query );
		
		}, 1 );
		
		add_action( 'get_sidebar', function( $name ) {
		
			HM_Time_Stack::instance()->add_event( 'get_sidebar', 'get_sidebar - ' . $name );
		
		}, 1 );
		
		add_action( 'wp_footer', function() {
		
			HM_Time_Stack::instance()->add_event( 'wp_footer' );
		
		}, 1 );

	}
	
	public function get_start_time() {
		return $this->start_time;
	}
}

class HM_Time_Stack_Operation {

	public $start_time;
	public $end_time;
	public $duration;
	public $id;
	public $label;
	public $is_open;
	private $open_operation;
	public $children;

	public function __construct( $id, $label = '' ) {
	
		$this->children = array();
		$this->id = $id;
		$this->start_time = hm_time_stack_time();
		$this->label = $label;
		$this->is_open = true;
	}
	
	public function end() {
		$this->end_time = hm_time_stack_time();
		$this->duration = round( $this->end_time - $this->start_time, 4 );
		$this->is_open = false;
	}
	
	public function add_operation( $operation ) {
	
		if ( ! empty( $this->open_operation ) ) {
			$this->open_operation->add_operation( $operation );
		}
		
		else {
			$this->children[] = $operation;
			
			$this->open_operation = $operation;
		}
	}
	
	public function add_event( $event ) {
	
		if ( ! empty( $this->open_operation ) ) {
			$this->open_operation->add_event( $event );
		} else {
			$this->children[] = $event;
		}
	}
	
	public function end_operation( $operation ) {
				
		if ( ! empty( $this->open_operation ) ) {
			
			if ( $this->open_operation == $operation ) {
				$this->open_operation->end();
				$this->open_operation = null;
			}
			else {
				$this->open_operation->end_operation( $operation );
			
			}

		}
				
		if ( $operation === $this ) {
			
			$this->end();
		
		}
	}
	
	public function get_child_operation_by_id( $id ) {
	
		$return = null;
		
		foreach ( $this->children as $child ) {
			if ( $operation = $child->get_child_operation_by_id( $id ) ) {
				$return = $operation;
				break;
			}
		}
		
		if ( $this->is_open && $this->id == $id )
			$return = $this;		

		return $return;	
	}
	
	public function _print() {
	
		?>
		<li class="operation">
			<span class="title">
				operation: <strong><?php echo $this->label ? $this->label : $this->id ?></strong> <span class="duration"><?php echo $this->duration ?></span>
			</span>

			<?php if ( $this->is_open ) : ?>
				Warning: Not Ended;			
			<?php endif; ?>

			<ul>
			<?php foreach( $this->children as $operation ) : ?>
				
				<?php $operation->_print(); ?>
				
			<?php endforeach; ?>
			</ul>
		</li>
		<?php
	
	}

}

class HM_Time_Stack_Event extends HM_Time_Stack_Operation {

	public $id;
	public $time;
	
	function __construct( $id, $label = '' ) {
		
		$this->id = $id;
		$this->time = round( hm_time_stack_time() - HM_Time_Stack::instance()->get_start_time(), 4 );
		$this->children = array();
		$this->label = $label;
	}
	
	function _print() {
	
		?>
		<li class="event">
			<?php echo $this->label ? $this->label : $this->id ?><span class="duration"><?php echo $this->time ?> in
		</li>
		<?php
	
	}

}

function hm_time_stack_time() {

	$time = explode( ' ', microtime() );
	$time = $time[1] + $time[0];
	return $time;
}

HM_Time_Stack::instance();