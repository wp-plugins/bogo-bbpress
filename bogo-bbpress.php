<?php
/*
Plugin Name: Bogo bbPress
Description: Make Bogo work with bbPress
Plugin URI: http://wordpress.org/extend/plugins/bogo-bbpress/
Author: Markus Echterhoff
Author URI: http://www.markusechterhoff.com
Version: 3.1
License: GPLv3 or later
*/

require_once( 'includes/registered-strings.php' );
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

add_action( 'admin_notices', 'bogobbp_bogoxlib_check' );
function bogobbp_bogoxlib_check() {
	if ( !is_plugin_active( 'bogoxlib/bogoxlib.php' ) ) {
		echo '<div class="error"><p>Bogo bbPress requires BogoXLib to work. <a href="' . esc_url( network_admin_url('plugin-install.php?tab=plugin-information&plugin=bogoxlib' . '&TB_iframe=true&width=600&height=550' ) ) . '" class="thickbox" title="More info about BogoXLib">Install BogoXLib</a>, activate it and then re-activate Bogo bbPress. <b>Deactivated</b>.</p></div>';
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
	
	if( !is_plugin_active( 'bbpress/bbpress.php' ) || !is_plugin_active( 'bogo/bogo.php' ) || !is_plugin_active( 'bogoxlib/bogoxlib.php' ) ) {
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
add_filter( 'bogo_language_switcher_links', 'bogobbp_fix_language_switcher_links', 10, 2 );
function bogobbp_fix_language_switcher_links( $links, $args ) {
	if ( is_bbpress() ) {
		return bogoxlib_fix_language_switcher_links( $links );
	}
	return $links;
}

add_action( 'template_redirect', 'bogobbp_redirect_to_localized_url', 9 );
function bogobbp_redirect_to_localized_url() {
	if ( is_bbpress() ) {
		bogoxlib_redirect_user_to_localized_url();
	}
}

add_action( 'plugins_loaded' , 'bogobbp_translate_emails', ~PHP_INT_MAX );
function bogobbp_translate_emails() {
	if ( !is_plugin_active( 'bogoxlib/bogoxlib.php' ) ) {
		return;
	}
	$root_slug = '/' . get_option( '_bbp_root_slug' ) . '/';
	$topics_slug = '/' . get_option( '_bbp_topics_slug' ) . '/';
	$slugs = array( $root_slug, $topics_slug );
	bogoxlib_localize_emails_for( 'bbpress', $slugs, bogobbp_registered_strings() );
	add_filter( 'bogoxlib_translate_email', 'bogobbp_translate_email', 10, 3 );
}

function bogobbp_translate_email( $email, $locales_by_email, $email_locale ) {

	// don't mess with other plugins mail
	// if ( !is_bbpress() ) { // returns true for buddypress sites...
	$bbp_root_slug = '/' . get_option( '_bbp_root_slug' ) . '/';
	$current_component_path = trailingslashit( bogoxlib_get_current_component_path() );
	if ( !bogoxlib_starts_with( $current_component_path, $bbp_root_slug ) ) {
		return $email;
	}
				
	// set up default from and from names to override bbpress spam-folder-me-now noreply address
	// see wp_mail() defaults at https://core.trac.wordpress.org/browser/tags/4.1.1/src/wp-includes/pluggable.php#L0
	$from_name = 'WordPress';
	$sitename = strtolower( $_SERVER['SERVER_NAME'] );
    if ( substr( $sitename, 0, 4 ) == 'www.' ) {
            $sitename = substr( $sitename, 4 );
    }
	$from_email = 'wordpress@' . $sitename;
	$from_email = apply_filters( 'wp_mail_from', $from_email );
	$from_name = apply_filters( 'wp_mail_from_name', 'WordPress' );

	$email['to'] = ''; // replace recipient as we don't want a copy of this
	$email['headers'][0] = "From: $from_name <$from_email>";
	
	// bin recipient addresses according to locale	
	$recipients = array();
	$length = count( $email['headers'] );
	$default_locale = bogo_get_default_locale();
	for ( $i = 1; $i < $length; $i++ ) {
		$address = substr( $email['headers'][$i], 5, strlen( $email['headers'][$i] ) - 5 );
		$locale = $locales_by_email[$address];
		if ( !$locale ) {
			$locale = $default_locale;
		}
		$recipients[$locale][] = $address;
	}

	$ret = array();

	foreach ( $recipients as $locale => $addresses ) {
		$new_email = $email;
		$new_email['headers'] = array( $email['headers'][0] ); // 'From:' stays the same
		foreach ( $addresses as $address ) {
			$new_email['headers'][]= 'Bcc: ' . $address;
		}
		if ( $locale != $email_locale ) {
			$new_email['subject'] = bogoxlib_retranslate_this_email_field( $email['subject'], 'bbpress', $locale );
			$new_email['message'] = bogoxlib_retranslate_this_email_field( $email['message'], 'bbpress', $locale );
		}
		$ret[]= $new_email;
	}

	return $ret;
}

/* debugging fun
remove_action( 'bbp_new_topic', 'bbp_notify_forum_subscribers', 11 );
add_action( 'bbp_new_topic', 'bogobbp_notify_forum_subscribers', 11, 4 );
function bogobbp_notify_forum_subscribers( $topic_id = 0, $forum_id = 0, $anonymous_data = false, $topic_author = 0 ) {
	bogoxlib_log( $topic_id );
	bogoxlib_log( $forum_id );
	bogoxlib_log( $anonymous_data );
	bogoxlib_log( $topic_author );
}

add_action( 'plugins_loaded', 'abogobbp_notify_forum_subscribers', PHP_INT_MAX );
function abogobbp_notify_forum_subscribers() {
	bbp_notify_forum_subscribers( 742, 75, 0, 2 );
}
*/

?>
