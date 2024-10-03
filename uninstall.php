<?php

/**
* Trigger this file on plugin unintsall
*
* @package eos_events
*/

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Access the database via SQL
global $wpdb;
$wpdb->query( "DELETE FROM wp_posts WHERE post_type = 'event'" );
$wpdb->query( "DELETE FROM wp_postmeta WHERE post_id NOT IN (SELECT id FROM wp_posts)" );