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

add_filter( 'bogo_language_switcher', 'bogo_bbpress_fix_language_switcher_links' );
function bogo_bbpress_fix_language_switcher_links( $output ) {

	if ( !is_bbpress() ) {
		return $output;
	}
	
	$dom = new DOMDocument;
	$dom->loadHTML( $output );
	foreach( $dom->getElementsByTagName( 'li' ) as $li) { // $li is of class DOMNode
	
		list( $item_locale_css, $item_lang ) = explode( ' ', $li->attributes->getNamedItem( 'class' )->value);
		$item_locale = str_replace( '-', '_', $item_locale_css);
		
		// skip item belonging to current locale
		$current_locale = get_query_var( 'lang' );
		if ( $current_locale == $item_locale ) {
			continue;
		}

		// construct uri
		$uri_site_path = bogo_bbpress_get_site_path();
		$path_remaining = substr( $_SERVER['REQUEST_URI'], strlen( $uri_site_path ), strlen( $_SERVER['REQUEST_URI'] ) - strlen( $uri_site_path ) );
		if ( $item_locale == bogo_get_default_locale() ) {
			$path_remaining = substr( $path_remaining, 3, strlen( $path_remaining ) - 3 );
			$uri =  $uri_site_path . $path_remaining;
		} else {
			$uri = $uri_site_path . '/'. $item_lang . $path_remaining;
		}
		
		$a = $dom->createDocumentFragment();
		$a->appendXML( '<a href="' . esc_url( $uri ) . '" hreflang="' . $item_locale_css . '" rel="alternate">' . $li->nodeValue . '</a>');
		$li->nodeValue = '';
		$li->appendChild( $a );
	}
	
	return $dom->saveHTML();
}

function bogo_bbpress_get_site_path() {
	$parts = parse_url( site_url() );
	if ( !isset( $parts['path'] ) ) {
		return '/';
	}
	return $parts['path'];
}

?>
