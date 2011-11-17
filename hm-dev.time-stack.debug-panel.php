<?php

class Debug_Bar_Time_Stack extends Debug_Bar_Panel {
	function init() {
		$this->title( 'Time Stack' );
	}

	function prerender() {
		$this->set_visible( true );
	}

	function render() {
		HM_Time_Stack::instance()->printStack();
	}

}