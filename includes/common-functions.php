<?php

define( 'BOGOCOMM_FUNCTIONS', true );

function bogocomm_get_site_path() {
	$parts = parse_url( site_url() );
	if ( !isset( $parts['path'] ) ) {
		return '/';
	}
	return $parts['path'];
}

function bogocomm_localize_current_url( $locale ) {
	$current_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	return bogocomm_localize_url( $current_url, $locale );
}

function bogocomm_delocalize_url( $url ) {
	return bogocomm_replace_url_lang_path( $url, '' );
}

function bogocomm_localize_url( $url, $locale ) {
	return bogocomm_replace_url_lang_path( $url, bogo_lang_slug( $locale ) );
}

function bogocomm_replace_url_lang_path( $url, $replacement = '' ) {

	$parts = parse_url( $url );
	$site_path = bogocomm_get_site_path();
	
	// canonicalize to trailing slash
	if ( !isset( $parts['path'] ) ){
		$parts['path'] = '/';
	}
	
	// save path of wp install
	if ( $site_path != '/' ) {
		$parts['path'] = str_replace( $site_path, '', $parts['path'] );
	}
	
	// do not use lang in url for default locale (or when replacement is empty, i.e. removal)
	if ( $replacement && $replacement == bogo_lang_slug( bogo_get_default_locale() ) ) {
		$lang_path = '';
	} else {
		$lang_path = '/' . $replacement;
	}
	
	// add lang to path, possibly replacing existing lang path fragment
	if ( !preg_match( '@^/'.bogo_get_lang_regex().'/@', $parts['path'] ) ) {
		$parts['path'] = $lang_path . $parts['path']; 
	} else {
		$parts['path'] = preg_replace( '@^/'.bogo_get_lang_regex().'(/.*)@', $lang_path.'$2', $parts['path'] );
	}
	
	// restore path of wp install
	if ( $site_path != '/' ) {
		$parts['path'] = $site_path . $parts['path'];
	}
	
	return bogocomm_unparse_url( $parts );
}

function bogocomm_unparse_url($parsed_url) {
  $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
  $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
  $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
  $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
  $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
  $pass     = ($user || $pass) ? "$pass@" : '';
  $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
  $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
  $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
  return "$scheme$user$pass$host$port$path$query$fragment";
}

function bogocomm_update_rewrite_rule_query_string( $qs ) {
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

function bogocomm_starts_with( $haystack, $needle ) {
    return substr( $haystack, 0, strlen( $needle ) ) === $needle;
}

?>
