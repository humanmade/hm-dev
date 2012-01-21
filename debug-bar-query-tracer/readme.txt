=== Debug Bar Query Tracer ===
Contributors: inxilpro
Donate link: 
Tags: debug bar, performance, backtrace, wpdb
Requires at least: 3.2
Tested up to: 3.2
Stable tag: 0.1.1

A Debug Bar plugin that lets you trace what plugins are causing database queries.

== Description ==

The Debug Bar Query Tracer plugin backtraces all calls to `WPDB::query()` and determines:

* which plugin caused that database query (it ignores all queries that are a part of normal Wordpress activity), and
* the function chain that led to that query.  

It then displays that information on an additional panel in the [Debug Bar](http://wordpress.org/extend/plugins/debug-bar/)
plugin (which is required).

== Installation ==

1. Install the [Debug Bar](http://wordpress.org/extend/plugins/debug-bar/) plugin
2. Activate the Debug Bar plugin
3. Install the Query Tracer plugin
4. Activate the Query Tracer plugin

== Frequently Asked Questions ==

Feel free to ask questions [on my website](http://cmorrell.com/open-source/wordpress-plugins/debug-bar-query-tracer) and
I will update this section with any frequent questions.

== Screenshots ==

1. The Query Tracer Panel

== Changelog ==

= 0.1 =
* Initial release
= 0.1.1 =
* Made sure everything is ready for internationalization
= 0.1.2 = 
* Bugfixes
* Added better handling for instances where not queries were caused by plugins