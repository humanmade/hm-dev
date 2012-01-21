<?php
/*
Plugin Name: Debug Bar Query Tracer
Version: 0.1
Plugin URI: http://cmorrell.com/open-source/wordpress-plugins/debug-bar-query-tracer
Description: Debug Bar plugin that lets you trace what plugins initiate database queries
Author: Chris Morrell
Author URI: http://cmorrell.com

	Copyright 2011 Chris Morrell <http://cmorrell.com>

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
	
*/

// Define plugin's path for later use
define('GALAHAD_QUERY_TRACER_PATH', untrailingslashit(dirname(__FILE__)));

// TODO: Check that Debug Bar is installed

// Load Tracer & instatiate
require_once GALAHAD_QUERY_TRACER_PATH . '/Tracer.php';
Galahad_Query_Tracer::instance();

// Add panel to Debug Bar
add_filter('debug_bar_panels', 'galahad_query_tracer_register');
function galahad_query_tracer_register($panels)
{
	require_once GALAHAD_QUERY_TRACER_PATH . '/Panel.php';
	$panels[] = new Galahad_Query_Tracer_Panel;
	return $panels;
}