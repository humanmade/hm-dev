<?php

// define HM_DEV_EMAIL in your wp-config to override the email address that mail is redirected to
if ( ! defined( 'HM_DEV_EMAIL' ) )
	define( 'HM_DEV_EMAIL', 'dev@hmn.md' );

/**
 * Hook into wp_mail and force all emails to be redirected to a dev email address
 *
 * Setting HM_DEV_EMAIL to false will disable the redirect and allow
 * all email to carry on to the original recipient.
 */
add_filter( 'wp_mail', function( $args ) {

	if ( HM_DEV_EMAIL !== false )
		$args['to'] = HM_DEV_EMAIL;

	return $args;

} );