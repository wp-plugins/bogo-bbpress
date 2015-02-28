<?php
/*
Plugin Name: Bogo BBPress
Description: Make Bogo work with BBPress
Plugin URI: http://wordpress.org/extend/plugins/bogo-bbpress/
Author: Markus Echterhoff
Author URI: http://www.markusechterhoff.com
Version: 3.0
License: GPLv3 or later
*/

require_once( 'includes/common-functions.php' );
require_once( 'includes/common-filters.php' );
require_once( 'includes/notifications.php' );
require_once( 'includes/admin-notifications.php' );

register_activation_hook( __FILE__, 'bogobbp_activate');
function bogobbp_activate() {
	bogobbp_flush();
}

register_deactivation_hook( __FILE__, 'bogobbp_deactivate');
function bogobbp_deactivate() {
	bogobbp_flush();
}

function bogobbp_flush() {
	global $wp_rewrite;
   	$wp_rewrite->flush_rules();
}

add_filter( 'rewrite_rules_array', 'bogobbp_insert_rewrite_rules', 11 );
function bogobbp_insert_rewrite_rules( $rules ) {

	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if( !is_plugin_active( 'bbpress/bbpress.php' ) || !is_plugin_active( 'bogo/bogo.php' ) ) {
		return $rules;
	}

	$langs = bogo_get_lang_regex() . '/';
	$forums = trailingslashit( bbp_get_root_slug() );
	$topics = trailingslashit( bbp_get_topic_archive_slug() );
	$newrules = array();
	foreach ( $rules as $pattern => $query_string ) {
		if ( bogocomm_starts_with( $pattern, $forums ) ||
				bogocomm_starts_with( $pattern, $topics ) ) {
			$newrules[$langs.$pattern] = bogocomm_update_rewrite_rule_query_string( $query_string );
		}
	}

	return $newrules + $rules;
}

?>
