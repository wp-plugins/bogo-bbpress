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

require_once( 'includes/notifications.php' );
require_once( 'includes/admin-notifications.php' );
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

add_action( 'admin_notices', 'bogobbp_bogoxlib_check' );
function bogobbp_bogoxlib_check() {
	if ( !is_plugin_active( 'bogoxlib/bogoxlib.php' ) ) {
		echo '<div class="error"><p>Bogo BBPress requires BogoXLib to work. <a href="' . esc_url( network_admin_url('plugin-install.php?tab=plugin-information&plugin=bogoxlib' . '&TB_iframe=true&width=600&height=550' ) ) . '" class="thickbox" title="More info about BogoXLib">Install BogoXLib</a>, activate it and then re-activate Bogo BBPress. <b>Deactivated</b>.</p></div>';
		deactivate_plugins( 'bogo-bbpress/bogo-bbpress.php' );
	}
}

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
	
	if( !is_plugin_active( 'bbpress/bbpress.php' ) || !is_plugin_active( 'bogo/bogo.php' ) ) {
		return $rules;
	}

	$langs = bogo_get_lang_regex() . '/';
	$forums = trailingslashit( bbp_get_root_slug() );
	$topics = trailingslashit( bbp_get_topic_archive_slug() );
	$newrules = array();
	foreach ( $rules as $pattern => $query_string ) {
		if ( bogoxlib_starts_with( $pattern, $forums ) ||
				bogoxlib_starts_with( $pattern, $topics ) ) {
			$newrules[$langs.$pattern] = bogoxlib_update_rewrite_rule_query_string( $query_string );
		}
	}

	return $newrules + $rules;
}

add_filter( 'bogo_language_switcher', 'bogobbp_fix_language_switcher_links' );
function bogobbp_fix_language_switcher_links( $output ) {
	if ( is_bbpress() ) {
		return bogoxlib_fix_language_switcher_links( $output );
	}
	return $output;
}

?>
