=== wp-unit ===
Contributors: aaires, nunomorgadinho
Donate link: http://www.widgilabs.com
Tags: unit-testing, phpunit
Requires at least: 3.0
Tested up to: 3.0.4
Stable tag: 2.0

== Description ==

Enables you to create unit tests for your plugins, run and check the results in a centralized way.
Since version 2.0 it has phpunit bundled in so all you need to get started with unit testing is to install this plugin.

Its based on the WordPress automated system but distributed as a plugin. Also it does not require a new database and a new configuration file so when you create your unit tests pay special attention to your database access so you keep a consistent database.

Every unit test must extend PHPUnit_Framework_TestCase and be included in the testcase directory you will find under the wp-unit plugin directory.

To run the tests go to the Unit Testing menu in the admin panel and press the Run buttom. The results will be displayed on the same page.

Feel free to give us some feedback.

== Installation ==

1. Inside the WordPress admin, go to Plugins > Add New and search for 'wp-unit'.
2. Click 'Install'.

== Frequently Asked Questions ==

None yet.

== Changelog ==

2.0 Bundle phpunit.

1.1 Initial version

== Upgrade Notice ==

None.

== Screenshots ==

None.
