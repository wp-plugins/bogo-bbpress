<?php
/*
Plugin Name: Bogo bbPress
Description: Make Bogo work with bbPress
Plugin URI: http://wordpress.org/extend/plugins/bogo-bbpress/
Author: Markus Echterhoff
Author URI: http://www.markusechterhoff.com
Version: 2.0
License: GPLv3 or later
*/

register_activation_hook( __FILE__, 'bogo_bbpress_flush');
register_deactivation_hook( __FILE__, 'bogo_bbpress_flush');
function bogo_bbpress_flush() {
	global $wp_rewrite;
   	$wp_rewrite->flush_rules();
}

add_filter( 'rewrite_rules_array', 'bogo_bbbpress_insert_rewrite_rules', 11 );
function bogo_bbbpress_insert_rewrite_rules( $rules ) {

	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if( !is_plugin_active( 'bbpress/bbpress.php' ) || !is_plugin_active( 'bogo/bogo.php' ) ) {
		return $rules;
	}

	$langs = bogo_get_lang_regex() . '/';
	
	$newrules = array();
	
	$forums = trailingslashit( bbp_get_root_slug() );
	$topics = trailingslashit( bbp_get_topic_archive_slug() );
	foreach ( $rules as $pattern => $query_string ) {
		if ( bogo_bbbpress_starts_with( $pattern, $forums ) ||
				bogo_bbbpress_starts_with( $pattern, $topics ) ) {
			$newrules[$langs.$pattern] = bogo_bbpress_update_query_string( $query_string );
		}
	}

	return $newrules + $rules;
}

function bogo_bbbpress_starts_with( $haystack, $needle ) {
    return substr( $haystack, 0, strlen( $needle ) ) === $needle;
}

function bogo_bbpress_update_query_string( $qs ) {
	$ret = preg_replace_callback(
		'@\$matches\[(\d+)\]@',
		function( $matches ) {
			return '$matches[' . ( $matches[1] + 1 ) . ']';
		},
		$qs
	);
	$ret .= '&lang=$matches[1]';
	return $ret;
}

?>
